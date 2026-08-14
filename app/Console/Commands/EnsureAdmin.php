<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EnsureAdmin extends Command
{
    protected $signature = 'admin:ensure';

    protected $description = 'Provision the initial admin account from ADMIN_* env vars. Idempotent.';

    public function handle(): int
    {
        $email = (string) env('ADMIN_EMAIL', '');
        $name  = (string) env('ADMIN_NAME', 'Admin');

        if ($email === '') {
            $this->warn('[admin:ensure] ADMIN_EMAIL is empty — skipping admin provisioning.');
            return self::SUCCESS;
        }

        if (! Schema::hasTable('users')) {
            $this->warn('[admin:ensure] users table not present yet — skipping.');
            return self::SUCCESS;
        }

        // Add must_change_password column on legacy schemas that predate it.
        if (! Schema::hasColumn('users', 'must_change_password')) {
            DB::statement('ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0');
            $this->line('[admin:ensure] Added must_change_password column to users.');
        }

        $existing = DB::table('users')->where('email', $email)->first();
        if ($existing !== null) {
            $this->line("[admin:ensure] Admin '{$email}' already exists — leaving password untouched.");
            return self::SUCCESS;
        }

        $password = (string) env('ADMIN_PASSWORD', '');
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

        $this->info("[admin:ensure] Created admin '{$email}'.");
        if ($generated) {
            // Printed once. Operator must copy from deploy logs.
            $this->warn('[admin:ensure] Generated one-time admin password (copy now, will not be shown again):');
            $this->line("    {$password}");
        } else {
            $this->line('[admin:ensure] Used ADMIN_PASSWORD from environment.');
        }
        $this->line('[admin:ensure] Admin is flagged must_change_password=1 — first login will force a new password.');

        return self::SUCCESS;
    }
}
