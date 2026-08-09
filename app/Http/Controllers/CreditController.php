<?php
namespace App\Http\Controllers;
use App\Models\{CreditTransaction, Topup};
use App\Services\OmiseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class CreditController extends Controller {
    public function __construct(private OmiseService $omise) {}

    public function index() {
        $transactions = CreditTransaction::where('user_id', auth()->id())
            ->latest('created_at')->paginate(30);
        $pendingTopup = Topup::where('user_id', auth()->id())->where('status','pending')->latest()->first();
        return view('credits.index', compact('transactions','pendingTopup'));
    }

    /** Charge a tokenized card (Omise.js). Redirects to 3-D Secure page if required. */
    public function topupStore(Request $request) {
        $request->validate([
            'amount'      => 'required|numeric|min:100|max:100000',
            'omise_token' => 'required|string',
        ]);

        if (!config('omise.secret_key')) {
            return back()->with('error', 'ระบบยังไม่ได้ตั้งค่าคีย์ Omise กรุณาติดต่อผู้ดูแลระบบ');
        }

        $topup = Topup::create([
            'user_id' => auth()->id(),
            'amount'  => $request->amount,
            'status'  => 'pending',
        ]);

        $returnUri = route('credits.topup.return', $topup);
        $result = $this->omise->createCharge($request->amount, $request->omise_token, $returnUri, 'เติมเครดิต iPart Store #'.$topup->id);

        if (!$result['success']) {
            $topup->update(['status' => 'failed']);
            return back()->with('error', 'ไม่สามารถทำรายการชำระเงินได้: '.($result['error'] ?? 'unknown error'));
        }

        $charge = $result['data'];
        $topup->update(['charge_id' => $charge['id']]);

        if (($charge['status'] ?? null) === 'successful') {
            $this->markPaid($topup, $charge['id']);
            return redirect()->route('credits.index')->with('success', 'เติมเครดิตสำเร็จแล้ว');
        }

        if (!empty($charge['authorize_uri'])) {
            return redirect()->away($charge['authorize_uri']);
        }

        $topup->update(['status' => 'failed']);
        return back()->with('error', 'การชำระเงินไม่สำเร็จ: '.($charge['failure_message'] ?? 'ถูกปฏิเสธโดยธนาคาร'));
    }

    /** Return URL after 3-D Secure authorization. */
    public function topupReturn(Topup $topup) {
        abort_unless($topup->user_id === auth()->id(), 403);

        if ($topup->status === 'paid') {
            return redirect()->route('credits.index')->with('success', 'เติมเครดิตสำเร็จแล้ว');
        }

        $result = $this->omise->getCharge($topup->charge_id);
        if ($result['success'] && ($result['data']['status'] ?? null) === 'successful') {
            $this->markPaid($topup, $topup->charge_id);
            return redirect()->route('credits.index')->with('success', 'เติมเครดิตสำเร็จแล้ว');
        }

        $topup->update(['status' => 'failed']);
        return redirect()->route('credits.index')->with('error', 'การชำระเงินไม่สำเร็จหรือถูกยกเลิก');
    }

    /** Create a PromptPay QR charge and show the QR to the user. */
    public function topupQrStore(Request $request) {
        $request->validate(['amount' => 'required|numeric|min:20|max:100000']);

        if (!config('omise.secret_key')) {
            return back()->with('error', 'ระบบยังไม่ได้ตั้งค่าคีย์ Omise กรุณาติดต่อผู้ดูแลระบบ');
        }

        $topup = Topup::create(['user_id' => auth()->id(), 'amount' => $request->amount, 'status' => 'pending']);

        $result = $this->omise->createPromptPayCharge($request->amount, 'เติมเครดิต iPart Store #'.$topup->id);
        if (!$result['success']) {
            $topup->update(['status' => 'failed']);
            return back()->with('error', 'ไม่สามารถสร้าง QR ได้: '.($result['error'] ?? 'unknown error'));
        }

        $charge = $result['data'];
        $topup->update(['charge_id' => $charge['id']]);

        $qrImage = $charge['source']['scannable_code']['image']['download_uri'] ?? null;
        if (!$qrImage) {
            $topup->update(['status' => 'failed']);
            return back()->with('error', 'ไม่พบ QR Code จากผู้ให้บริการ');
        }

        return redirect()->route('credits.index')->with('qrTopup', ['id' => $topup->id, 'image' => $qrImage, 'amount' => $topup->amount]);
    }

    /** Polled by the QR page via JS to detect payment completion. */
    public function topupQrStatus(Topup $topup) {
        abort_unless($topup->user_id === auth()->id(), 403);

        if ($topup->status === 'paid') {
            return response()->json(['status' => 'paid']);
        }

        $result = $this->omise->getCharge($topup->charge_id);
        if ($result['success'] && ($result['data']['status'] ?? null) === 'successful') {
            $this->markPaid($topup, $topup->charge_id);
            return response()->json(['status' => 'paid']);
        }

        return response()->json(['status' => $topup->status]);
    }

    /** Downloadable PDF receipt for one credit transaction (own transactions only). */
    public function receipt(CreditTransaction $transaction) {
        abort_unless($transaction->user_id === auth()->id(), 403);
        $pdf = Pdf::loadView('credits.receipt', ['tx' => $transaction, 'user' => auth()->user()])
            ->setPaper('a5');
        return $pdf->download('receipt-'.$transaction->id.'.pdf');
    }

    /** Manual re-check (kept for the "ตรวจสอบสถานะ" button). */
    public function topupCheck(Topup $topup) {
        return $this->topupReturn($topup);
    }

    /** Omise webhook: POST from Omise servers. Omise has no HMAC signature, so we never trust
     *  the payload directly — re-fetch the charge from the API (server-to-server, secret-key
     *  authenticated) and only credit based on that verified response. */
    public function webhook(Request $request) {
        $event    = $request->input('key');
        $chargeId = $request->input('data.id');

        if ($event === 'charge.complete' && $chargeId) {
            $result = $this->omise->getCharge($chargeId);
            if ($result['success'] && ($result['data']['status'] ?? null) === 'successful') {
                $topup = Topup::where('charge_id', $chargeId)->first();
                if ($topup && $topup->status === 'pending') {
                    $this->markPaid($topup, $chargeId);
                }
            }
        }

        Log::info('Omise webhook received', ['key' => $event, 'charge_id' => $chargeId]);
        return response()->json(['received' => true]);
    }

    private function markPaid(Topup $topup, string $chargeId): void {
        DB::transaction(function () use ($topup, $chargeId) {
            $topup->refresh();
            if ($topup->status === 'paid') return;

            $user = $topup->user;
            $balanceBefore = $user->balance;
            $user->increment('balance', $topup->amount);
            $user->refresh();

            $user->creditTransactions()->create([
                'type' => 'topup', 'amount' => $topup->amount,
                'balance_before' => $balanceBefore, 'balance_after' => $user->balance,
                'description' => 'เติมเครดิตผ่านบัตรเครดิต (Omise) #'.$topup->id,
            ]);

            $topup->update(['status' => 'paid', 'charge_id' => $chargeId]);

            if ($user->email) {
                \Illuminate\Support\Facades\Mail::to($user->email)->queue(new \App\Mail\TopupSuccessMail($topup->fresh(['user'])));
            }
        });
    }
}
