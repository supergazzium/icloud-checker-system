<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Topup extends Model
{
    protected $fillable = [
        'user_id', 'amount', 'status',
        'bank_account_id', 'slip_path', 'slip_uploaded_at',
        'transfer_reference', 'transfer_date',
        'reviewed_by', 'reviewed_at', 'rejection_reason',
        // Legacy Omise columns — kept nullable for historical rows.
        'link_id', 'payment_uri', 'charge_id',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'slip_uploaded_at' => 'datetime',
        'transfer_date'    => 'date',
        'reviewed_at'      => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPendingReview(): bool
    {
        return $this->status === 'pending_review';
    }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'paid'], true);
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
