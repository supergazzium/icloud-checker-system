<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\{BankAccount, CreditTransaction, Topup};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CreditController extends Controller
{
    public function index()
    {
        $transactions = CreditTransaction::where('user_id', auth()->id())
            ->latest('created_at')->paginate(30);

        $pendingTopups = Topup::where('user_id', auth()->id())
            ->where('status', 'pending_review')
            ->latest('created_at')->get();

        $bankAccounts = BankAccount::active()->get();

        return view('credits.index', compact('transactions', 'pendingTopups', 'bankAccounts'));
    }

    public function topupStore(Request $request)
    {
        $validated = $request->validate([
            'amount'             => 'required|numeric|min:1|max:1000000',
            'bank_account_id'    => 'required|integer|exists:bank_accounts,id',
            'transfer_reference' => 'required|string|max:100',
            'transfer_date'      => 'required|date|before_or_equal:today',
            'slip'               => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        // Only accept transfers to active accounts.
        $account = BankAccount::active()->findOrFail($validated['bank_account_id']);

        // Duplicate-submission guard: same reference + same bank account within
        // the last 7 days from any user usually means an accidental double-submit.
        $duplicate = Topup::where('bank_account_id', $account->id)
            ->where('transfer_reference', $validated['transfer_reference'])
            ->where('created_at', '>=', now()->subDays(7))
            ->whereIn('status', ['pending_review', 'approved', 'paid'])
            ->exists();
        if ($duplicate) {
            return back()->withInput()->with(
                'error',
                'เลขที่อ้างอิงนี้ถูกใช้ไปแล้วภายใน 7 วันที่ผ่านมา หากคุณโอนซ้ำจริง กรุณาติดต่อผู้ดูแลระบบ'
            );
        }

        $path = $request->file('slip')->storeAs(
            'topup-slips/'.auth()->id(),
            (string) Str::uuid().'.'.$request->file('slip')->getClientOriginalExtension(),
            'public'
        );

        $topup = Topup::create([
            'user_id'            => auth()->id(),
            'bank_account_id'    => $account->id,
            'amount'             => $validated['amount'],
            'status'             => 'pending_review',
            'slip_path'          => $path,
            'slip_uploaded_at'   => now(),
            'transfer_reference' => $validated['transfer_reference'],
            'transfer_date'      => $validated['transfer_date'],
        ]);

        return redirect()
            ->route('credits.topup.show', $topup)
            ->with('success', 'ส่งคำขอเติมเครดิตแล้ว รอผู้ดูแลระบบตรวจสอบ');
    }

    public function topupShow(Topup $topup)
    {
        abort_unless($topup->user_id === auth()->id(), 403);
        $topup->load('bankAccount', 'reviewer');
        return view('credits.topup-show', compact('topup'));
    }

    /** Gated slip download — owner OR admin only. */
    public function topupSlip(Topup $topup): Response
    {
        $user = auth()->user();
        abort_unless($topup->user_id === $user->id || $user->isAdmin(), 403);
        abort_unless($topup->slip_path && Storage::disk('public')->exists($topup->slip_path), 404);

        return Storage::disk('public')->response($topup->slip_path);
    }

    public function receipt(CreditTransaction $transaction)
    {
        abort_unless($transaction->user_id === auth()->id(), 403);
        $pdf = Pdf::loadView('credits.receipt', ['tx' => $transaction, 'user' => auth()->user()])
            ->setPaper('a5');
        return $pdf->download('receipt-'.$transaction->id.'.pdf');
    }
}
