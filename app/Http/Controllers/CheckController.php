<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\{ApiLog, Order, Service};
use App\Services\IFreeICloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class CheckController extends Controller
{
    /** Reject duplicate identical submits within this window. */
    private const IDEMPOTENCY_SECONDS = 60;

    /** Max retries when MySQL raises a deadlock. */
    private const DEADLOCK_MAX_ATTEMPTS = 3;

    public function __construct(private IFreeICloudService $api) {}

    public function index()
    {
        $services = Service::where('active', 1)->orderBy('sort_order')->get();
        return view('check.index', compact('services'));
    }

    public function store(Request $request)
    {
        // Normalise up-front so validation and idempotency see the same shape
        // the DB will eventually store. LKQD-2YD-439 → LKQD2YD439.
        $normalised = $this->normaliseSerial((string) $request->input('imei_serial', ''));
        $request->merge(['imei_serial' => $normalised]);

        $validated = $request->validate([
            'service_id'  => 'required|integer|exists:services,id',
            'imei_serial' => 'required|string|regex:/^[A-Z0-9]{8,15}$/',
        ], [
            'imei_serial.regex' => __('check.imei_format_error'),
        ], [
            'service_id'  => __('check.service'),
            'imei_serial' => __('check.imei_serial_attribute'),
        ]);

        /** @var \App\Models\User $user */
        $user    = $request->user();
        $service = Service::find($validated['service_id']);

        // Service must exist AND be active. A stale form (tab left open while
        // admin deactivates the service) should surface a real error.
        if ($service === null || ! $service->active) {
            return back()->with('error', __('check.service_unavailable'))->withInput();
        }

        // Device-type / serial shape mismatch — reject before charging.
        $shape    = $this->classifySerial($normalised);
        $mismatch = $this->deviceMismatch($service->device_type, $shape);
        if ($mismatch !== null) {
            return back()->with('error', $mismatch)->withInput();
        }

        // Luhn — catches typo'd IMEIs before we spend an upstream credit.
        if ($shape === 'imei' && ! $this->luhnValid($normalised)) {
            return back()->with('error', __('check.imei_luhn_invalid'))->withInput();
        }

        // Idempotency guard: a duplicate submit within N seconds returns the
        // existing order rather than creating a second one.
        $recent = Order::where('user_id', $user->id)
            ->where('service_id', $service->id)
            ->where('imei_serial', $normalised)
            ->where('created_at', '>=', now()->subSeconds(self::IDEMPOTENCY_SECONDS))
            ->latest('id')
            ->first();
        if ($recent !== null) {
            return redirect()->route('orders.show', $recent->id);
        }

        try {
            $order = $this->runCheckWithRetry($user->id, $service, $normalised);
        } catch (InsufficientCreditException) {
            return back()->with('error', __('check.insufficient_credit'))->withInput();
        } catch (Throwable $e) {
            // Never leak DB or SDK messages to end users. Include a short
            // reference id in the response so support can find the log line.
            $ref = Str::upper(Str::random(8));
            Log::error('[check.store] unexpected failure', [
                'ref'         => $ref,
                'user_id'     => $user->id,
                'service_id'  => $service->id,
                'imei_serial' => $normalised,
                'exception'   => $e::class,
                'message'     => $e->getMessage(),
            ]);
            return back()->with('error', __('check.system_error', ['ref' => $ref]))->withInput();
        }

        return redirect()->route('orders.show', $order->id);
    }

    /**
     * Run the whole check inside a transaction with lockForUpdate on the
     * user row. Retries on MySQL deadlock, which can happen when two
     * requests race the same user's balance under heavy load.
     */
    private function runCheckWithRetry(int $userId, Service $service, string $imei): Order
    {
        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                return DB::transaction(function () use ($userId, $service, $imei) {
                    return $this->processCheck($userId, $service, $imei);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                $isDeadlock = in_array($e->errorInfo[1] ?? 0, [1213, 1205], true);
                if (! $isDeadlock || $attempt >= self::DEADLOCK_MAX_ATTEMPTS) {
                    throw $e;
                }
                Log::warning('[check.store] deadlock retry', [
                    'user_id' => $userId,
                    'service_id' => $service->id,
                    'attempt' => $attempt,
                ]);
                usleep(random_int(50_000, 200_000));
            }
        }
    }

    /**
     * The full check happens inside one transaction:
     *   1. Lock the user row (prevents concurrent debits/refunds).
     *   2. Verify balance (recheck under lock — the pre-flight check earlier
     *      is only advisory).
     *   3. Insert the order + deduct credit + write the deduct ledger entry.
     *   4. Call the upstream. This is the SLOWEST step. Keep it inside the
     *      transaction so a mid-flight crash rolls the balance back.
     *   5. On success: populate result columns.
     *   6. On upstream failure: refund inside the same tx (no window where
     *      the user is billed for a failed check).
     *
     * If the upstream call throws / times out, the transaction rolls back
     * cleanly — no orphan order rows, no phantom debits.
     */
    private function processCheck(int $userId, Service $service, string $imei): Order
    {
        /** @var \App\Models\User $user */
        $user = \App\Models\User::whereKey($userId)->lockForUpdate()->firstOrFail();

        if ((float) $user->balance < (float) $service->sell_price) {
            // Typed exception caught by store() → friendly flash message.
            // Throwing inside the tx makes DB roll back automatically.
            throw new InsufficientCreditException();
        }

        $order = Order::create([
            'user_id'     => $user->id,
            'service_id'  => $service->id,
            'imei_serial' => $imei,
            'cost_price'  => $service->cost_price,
            'sell_price'  => $service->sell_price,
            'status'      => 'processing',
        ]);

        $balanceBefore = (float) $user->balance;
        $user->decrement('balance', $service->sell_price);
        $user->refresh();

        $user->creditTransactions()->create([
            'type'           => 'deduct',
            'amount'         => $service->sell_price,
            'balance_before' => $balanceBefore,
            'balance_after'  => $user->balance,
            'description'    => "Check: {$service->name_en} | {$imei}",
            'order_id'       => $order->id,
        ]);

        // Fire the low-balance notification only when we've just crossed
        // the threshold on THIS debit. Send outside the tx would be nicer
        // (queue jobs shouldn't hold DB locks) but the existing queue is
        // database-driven so the enqueue itself is a DB write anyway.
        $threshold = (float) config('app.low_balance_threshold', 50);
        if ($balanceBefore >= $threshold && $user->balance < $threshold && $user->email) {
            Mail::to($user->email)->queue(new \App\Mail\LowBalanceMail($user));
        }

        // Upstream call. If this throws, the whole tx rolls back — balance
        // is restored, order row disappears, ledger stays consistent.
        $result = $this->api->check($service->provider_service_id, $imei);
        if ($service->provider_service_id_2) {
            $result2 = $this->api->check($service->provider_service_id_2, $imei);
            $result  = $this->api->mergeResults($result, $result2);
        }

        ApiLog::create([
            'order_id'    => $order->id,
            'service_id'  => $service->provider_service_id,
            'imei_serial' => $imei,
            'http_code'   => $result['http_code'] ?? 0,
            'success'     => $result['success'] ? 1 : 0,
            'error_msg'   => $result['error'] ?? null,
            'duration_ms' => $result['duration_ms'] ?? null,
        ]);

        if ($result['success']) {
            $p = $result['parsed'];
            $order->update([
                'status'                    => 'success',
                // Identity
                'result_model'              => $p['model']          ?? null,
                'result_model_desc'         => $p['model_desc']     ?? null,
                'result_part_number'        => $p['part_number']    ?? null,
                'result_part_country'       => $p['part_country']   ?? null,
                'result_part_type'          => $p['part_type']      ?? null,
                'result_thumbnail'          => $p['thumbnail']      ?? null,
                // Identifiers
                'result_serial'             => $p['serial']         ?? null,
                'result_imei'               => $p['imei']           ?? null,
                'result_imei2'              => $p['imei2']          ?? null,
                // Hardware
                'result_color'              => $p['color']          ?? null,
                'result_storage'            => $p['storage']        ?? null,
                'result_region'             => $p['region']         ?? null,
                // Security
                'result_fmi'                => $p['fmi_status']     ?? null,
                'result_activation'         => $p['activation_status'] ?? null,
                'result_blacklist'          => $p['blacklist_status']  ?? null,
                'result_simlock'            => $p['simlock_status']    ?? null,
                'result_carrier'            => $p['carrier']        ?? null,
                'result_mdm'                => $p['mdm_status']     ?? null,
                // Warranty
                'result_warranty'           => $p['warranty']            ?? null,
                'result_coverage_end_date'  => $p['coverage_end_date']   ?? null,
                'result_ac_eligible'        => $p['ac_eligible']         ?? null,
                'result_technical_support'  => $p['technical_support']   ?? null,
                'result_repair_coverage'    => $p['repair_coverage']     ?? null,
                // Purchase
                'result_purchase_date'      => $p['purchase_date']      ?? null,
                'result_purchase_country'   => $p['purchase_country']   ?? null,
                // Unit state
                'result_replaced'           => $p['replaced_status'] ?? null,
                'result_replacement'        => $p['replacement']     ?? null,
                'result_refurbished'        => $p['refurbished']     ?? null,
                'result_demo_unit'          => $p['demo_unit']       ?? null,
                'result_loaner'             => $p['loaner']          ?? null,
                // Raw
                'response_text'             => $result['response'],
                'raw_response'              => $result['raw'],
                'processed_at'              => now(),
            ]);
            return $order;
        }

        // Upstream said no — refund atomically. User NEVER sees a debit
        // without a matching refund for a failed check.
        $refundBefore = (float) $user->balance;
        $user->increment('balance', $service->sell_price);
        $user->refresh();
        $user->creditTransactions()->create([
            'type'           => 'refund',
            'amount'         => $service->sell_price,
            'balance_before' => $refundBefore,
            'balance_after'  => $user->balance,
            'description'    => "คืนเครดิตอัตโนมัติ: ระบบตรวจสอบผิดพลาด | {$imei}",
            'order_id'       => $order->id,
        ]);

        $order->update([
            'status'        => 'error',
            'error_message' => $this->mapUpstreamError((string) ($result['error'] ?? '')),
            'raw_response'  => $result['raw'],
        ]);

        Log::warning('[check.store] upstream check failed', [
            'order_id'    => $order->id,
            'service_id'  => $service->provider_service_id,
            'imei_serial' => $imei,
            'http_code'   => $result['http_code'] ?? 0,
            'error'       => $result['error'] ?? null,
            'raw_body'    => is_string($result['raw'] ?? null)
                ? substr((string) $result['raw'], 0, 500)
                : null,
        ]);

        return $order;
    }

    // ---------- helpers ----------

    /** Strip everything non-alphanumeric, uppercase. */
    private function normaliseSerial(string $raw): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw) ?? '');
    }

    /**
     * Classify what the input looks like. Returns:
     *   'imei'   — exactly 15 digits (GSM device)
     *   'serial' — 8–14 alphanumeric with at least one letter (Apple serial)
     *   'other'  — doesn't match either shape
     */
    private function classifySerial(string $v): string
    {
        if (preg_match('/^\d{15}$/', $v)) return 'imei';
        if (preg_match('/^[A-Z0-9]{8,14}$/', $v) && preg_match('/[A-Z]/', $v)) return 'serial';
        return 'other';
    }

    /**
     * Return a user-facing error when service device_type doesn't accept
     * the given input shape. null = no mismatch, allow through.
     */
    private function deviceMismatch(string $deviceType, string $shape): ?string
    {
        if ($deviceType === 'macbook' && $shape === 'imei')  return __('check.mismatch_mac_needs_serial');
        if ($deviceType === 'ipad'    && $shape === 'other') return __('check.mismatch_ipad_needs_serial_or_imei');
        if ($deviceType === 'iphone'  && $shape === 'other') return __('check.mismatch_iphone_needs_serial_or_imei');
        return null;
    }

    /** Standard Luhn checksum — valid for real IMEIs and credit cards. */
    private function luhnValid(string $number): bool
    {
        $sum = 0;
        $len = strlen($number);
        for ($i = 0; $i < $len; $i++) {
            $d = (int) $number[$len - 1 - $i];
            if ($i % 2 === 1) {
                $d *= 2;
                if ($d > 9) $d -= 9;
            }
            $sum += $d;
        }
        return $sum % 10 === 0;
    }

    /**
     * Translate provider-side error strings into user-safe wording. Anything
     * we don't recognise falls back to a generic message.
     */
    private function mapUpstreamError(string $providerMessage): string
    {
        $lc = strtolower($providerMessage);
        if (str_contains($lc, 'insufficient') && str_contains($lc, 'balance')) {
            return __('check.upstream_system_error');
        }
        if (str_contains($lc, 'not found') || str_contains($lc, 'device not found')) {
            return __('check.upstream_device_not_found');
        }
        if (str_contains($lc, 'invalid imei') || str_contains($lc, 'invalid serial')) {
            return __('check.upstream_invalid_input');
        }
        if (str_contains($lc, 'unable to check')) {
            return __('check.upstream_unable');
        }
        return __('check.upstream_generic');
    }
}
