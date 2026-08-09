<?php
namespace App\Http\Controllers;
use App\Models\{Order, Service, ApiLog};
use App\Services\IFreeICloudService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckController extends Controller {
    public function __construct(private IFreeICloudService $api) {}

    public function index() {
        $services = Service::where('active', 1)->orderBy('sort_order')->get();
        return view('check.index', compact('services'));
    }

    public function store(Request $request) {
        $request->validate([
            'service_id'  => 'required|exists:services,id',
            'imei_serial' => 'required|string|min:8|max:20',
        ]);
        $user    = auth()->user();
        $service = Service::findOrFail($request->service_id);

        if ($user->balance < $service->sell_price) {
            return back()->with('error', __('check.insufficient_credit'));
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id'     => $user->id,
                'service_id'  => $service->id,
                'imei_serial' => trim($request->imei_serial),
                'cost_price'  => $service->cost_price,
                'sell_price'  => $service->sell_price,
                'status'      => 'processing',
            ]);

            $balanceBefore = $user->balance;
            $user->decrement('balance', $service->sell_price);
            $user->refresh();

            $user->creditTransactions()->create([
                'type' => 'deduct', 'amount' => $service->sell_price,
                'balance_before' => $balanceBefore, 'balance_after' => $user->balance,
                'description' => "Check: {$service->name_en} | {$request->imei_serial}",
                'order_id' => $order->id,
            ]);

            $threshold = (float) config('app.low_balance_threshold', 50);
            if ($balanceBefore >= $threshold && $user->balance < $threshold && $user->email) {
                \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\LowBalanceMail($user));
            }

            $result = $this->api->check($service->provider_service_id, $request->imei_serial);

            if ($service->provider_service_id_2) {
                $result2 = $this->api->check($service->provider_service_id_2, $request->imei_serial);
                $result  = $this->api->mergeResults($result, $result2);
            }

            ApiLog::create([
                'order_id' => $order->id, 'service_id' => $service->provider_service_id,
                'imei_serial' => $request->imei_serial, 'http_code' => $result['http_code'] ?? 0,
                'success' => $result['success'] ? 1 : 0, 'error_msg' => $result['error'] ?? null,
                'duration_ms' => $result['duration_ms'] ?? null,
            ]);

            if ($result['success']) {
                $p = $result['parsed'];
                $order->update([
                    'status'              => 'success',
                    'result_model'        => $p['model'],        'result_serial'   => $p['serial'],
                    'result_imei'         => $p['imei'],         'result_color'    => $p['color'],
                    'result_storage'      => $p['storage'],      'result_region'   => $p['region'],
                    'result_fmi'          => $p['fmi_status'],   'result_activation' => $p['activation_status'],
                    'result_blacklist'    => $p['blacklist_status'], 'result_simlock' => $p['simlock_status'],
                    'result_mdm'          => $p['mdm_status'],   'result_warranty' => $p['warranty'],
                    'result_purchase_date'=> $p['purchase_date'],'result_replaced' => $p['replaced_status'],
                    'response_text'       => $result['response'],'raw_response'    => $result['raw'],
                    'processed_at'        => now(),
                ]);
            } else {
                $refundBefore = $user->balance;
                $user->increment('balance', $service->sell_price);
                $user->refresh();
                $user->creditTransactions()->create([
                    'type' => 'refund', 'amount' => $service->sell_price,
                    'balance_before' => $refundBefore, 'balance_after' => $user->balance,
                    'description' => "คืนเครดิตอัตโนมัติ: ระบบตรวจสอบผิดพลาด | {$request->imei_serial}", 'order_id' => $order->id,
                ]);
                $order->update(['status' => 'error', 'error_message' => $result['error'], 'raw_response' => $result['raw']]);
            }
            DB::commit();
            return redirect()->route('orders.show', $order->id);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'System error: '.$e->getMessage());
        }
    }
}
