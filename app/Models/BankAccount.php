<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    protected $fillable = [
        'bank_name', 'account_name', 'account_number',
        'branch', 'notes', 'active', 'sort_order',
    ];

    protected $casts = [
        'active'     => 'boolean',
        'sort_order' => 'integer',
    ];

    public function topups()
    {
        return $this->hasMany(Topup::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true)->orderBy('sort_order')->orderBy('bank_name');
    }
}
