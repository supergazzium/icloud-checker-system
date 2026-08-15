<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Idempotent admin provisioner. Reads ADMIN_* and (optionally) ADMIN2_*
 * env vars and creates the corresponding admin rows if they don't exist.
 * Never modifies an existing row's password — safe to re-run on every
 * container boot.
 */
class EnsureAdmin extends Command
{
    protected $signature = 'admin:ensure';

    protected $description = 'Provision admin accounts from ADMIN_* / ADMIN2_* env vars. Idempotent.';

    /** Env-var prefixes we look for, in order. */
    private const ADMIN_SLOTS = ['ADMIN', 'ADMIN2'];

    public function handle(): int
    {
        if (! Schema::hasTable('users')) {
            $this->warn('[admin:ensure] users table not present yet — skipping.');
            return self::SUCCESS;
        }

        // Back-fill the must_change_password column on legacy schemas that
        // predate the security-hardening migration.
        if (! Schema::hasColumn('users', 'must_change_password')) {
            DB::statement('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0');
            $this->line('[admin:ensure] Added must_change_password column to users.');
        }

        foreach (self::ADMIN_SLOTS as $slot) {
            $this->provisionSlot($slot);
        }

        return self::SUCCESS;
    }

    private function provisionSlot(string $slot): void
    {
        $email = (string) env("{$slot}_EMAIL", '');
        $name  = (string) env("{$slot}_NAME", 'Admin');

        if ($email === '') {
            // ADMIN_EMAIL empty is worth noting; ADMIN2 optional so silent.
            if ($slot === 'ADMIN') {
                $this->warn('[admin:ensure] ADMIN_EMAIL is empty — no primary admin will be provisioned.');
            }
            return;
        }

        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing !== null) {
            $this->line("[admin:ensure] Admin '{$email}' ({$slot}) already exists — leaving password untouched.");
            return;
        }

        $password  = (string) env("{$slot}_PASSWORD", '');
        $generated = false;
        if ($password === '') {
            $password = Str::password(24, letters: true, numbers: true, symbols: true, spaces: false);
            $generated = true;
        }

        DB::table('users')->insert([
            'name'                 => $name,
            'email'                => $email,
            'password'             => Hash::make($password),
            'role'                 => 'admin',
            'balance'              => 0.00,
            'locale'               => 'th',
            'is_active'            => 1,
            'must_change_password' => 1,
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        $this->info("[admin:ensure] Created admin '{$email}' ({$slot}).");
        if ($generated) {
            // Printed once. Operator must copy from deploy logs — the hash
            // is stored, but the plaintext is not recoverable.
            $this->warn("[admin:ensure] Generated one-time password for {$email} (copy now, will not be shown again):");
            $this->line("    {$password}");
        } else {
            $this->line("[admin:ensure] Used {$slot}_PASSWORD from environment.");
        }
        $this->line("[admin:ensure] Admin flagged must_change_password=1 — first login forces a new password.");
    }
}
