<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class AdminBankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::orderBy('active', 'desc')
            ->orderBy('sort_order')
            ->orderBy('bank_name')
            ->get();
        return view('admin.bank-accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateAccount($request);
        BankAccount::create($validated);
        return back()->with('success', 'เพิ่มบัญชีธนาคารสำเร็จ');
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $validated = $this->validateAccount($request);
        $bankAccount->update($validated);
        return back()->with('success', 'อัปเดตบัญชีธนาคารสำเร็จ');
    }

    public function toggleActive(BankAccount $bankAccount)
    {
        $bankAccount->update(['active' => ! $bankAccount->active]);
        return back()->with('success', $bankAccount->active ? 'เปิดใช้งานแล้ว' : 'ปิดใช้งานแล้ว');
    }

    public function destroy(BankAccount $bankAccount)
    {
        // Never hard-delete if any topup references it — soft-disable instead.
        if ($bankAccount->topups()->exists()) {
            $bankAccount->update(['active' => false]);
            return back()->with('warning', 'บัญชีนี้มีประวัติเติมเครดิตอยู่ ระบบปิดใช้งานแทนการลบ');
        }
        $bankAccount->delete();
        return back()->with('success', 'ลบบัญชีธนาคารแล้ว');
    }

    private function validateAccount(Request $request): array
    {
        return $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_name'   => 'required|string|max:200',
            'account_number' => 'required|string|max:50',
            'branch'         => 'nullable|string|max:100',
            'notes'          => 'nullable|string|max:500',
            'active'         => 'sometimes|boolean',
            'sort_order'     => 'nullable|integer|min:0|max:9999',
        ]);
    }
}
