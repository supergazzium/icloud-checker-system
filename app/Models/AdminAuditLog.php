<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Generic admin activity trail — service imports, config changes,
 * anything that isn't money movement (money movement lives in
 * credit_transactions, which is a financial ledger, not an audit log).
 */
class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected $table = 'admin_audit_log';

    protected $fillable = [
        'admin_id', 'admin_ip', 'action',
        'subject_type', 'subject_id', 'meta',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * Record an admin action. Never throws — audit failure must not
     * break the underlying operation. Returns the created row or null.
     */
    public static function record(
        string $action,
        ?string $subjectType = null,
        ?string $subjectId = null,
        array $meta = [],
    ): ?self {
        try {
            return self::create([
                'admin_id'     => auth()->id(),
                'admin_ip'     => request()->ip(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'meta'         => $meta ?: null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable) {
            return null;
        }
    }
}
