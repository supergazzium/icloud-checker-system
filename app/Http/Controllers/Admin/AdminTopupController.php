<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTopupController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending_review');
        $allowedStatuses = ['pending_review', 'approved', 'rejected', 'all'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'pending_review';
        }

        $query = Topup::with('user', 'bankAccount', 'reviewer')
            ->latest('created_at');

        if ($status !== 'all') {
            if ($status === 'approved') {
                $query->whereIn('status', ['approved', 'paid']);
            } else {
                $query->where('status', $status);
            }
        }

        $topups = $query->paginate(30)->withQueryString();

        $pendingCount = Topup::where('status', 'pending_review')->count();

        return view('admin.topups.index', compact('topups', 'status', 'pendingCount'));
    }

    public function show(Topup $topup)
    {
        $topup->load('user', 'bankAccount', 'reviewer');
        return view('admin.topups.show', compact('topup'));
    }

    public function approve(Request $request, Topup $topup)
    {
        abort_unless($topup->isPendingReview(), 422, 'Topup is not pending review.');

        DB::transaction(function () use ($topup, $request) {
            $user = $topup->user()->lockForUpdate()->first();
            $before = $user->balance;
            $user->increment('balance', (float) $topup->amount);
            $user->refresh();

            $user->creditTransactions()->create([
                'type'           => 'topup',
                'amount'         => $topup->amount,
                'balance_before' => $before,
                'balance_after'  => $user->balance,
                'description'    => 'โอนธนาคาร #'.$topup->id.' — '.optional($topup->bankAccount)->bank_name,
                'admin_id'       => auth()->id(),
                'admin_ip'       => $request->ip(),
                'reference'      => $topup->transfer_reference,
            ]);

            $topup->update([
                'status'      => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        return redirect()->route('admin.topups.show', $topup)->with('success', 'อนุมัติการเติมเครดิตแล้ว');
    }

    public function reject(Request $request, Topup $topup)
    {
        abort_unless($topup->isPendingReview(), 422, 'Topup is not pending review.');

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $topup->update([
            'status'           => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by'      => auth()->id(),
            'reviewed_at'      => now(),
        ]);

        return redirect()->route('admin.topups.show', $topup)->with('success', 'ปฏิเสธการเติมเครดิตแล้ว');
    }
}
