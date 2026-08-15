<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Dev-only seeder: creates two test customer accounts with known
 * passwords and starting balance. Refuses to run when APP_ENV is
 * "production" — this data is exclusively for local iteration and
 * would be a real user-data footgun in prod.
 *
 * Idempotent: existing rows are refreshed (password + balance reset,
 * must_change_password cleared) so repeated runs give a predictable
 * starting state.
 *
 * Usage:
 *   docker compose exec app php artisan dev:seed-customers
 *   docker compose exec app php artisan dev:seed-customers --force  (bypass env guard)
 */
class SeedDevCustomers extends Command
{
    protected $signature = 'dev:seed-customers
        {--force : Bypass the APP_ENV=production safety guard (still risky).}';

    protected $description = 'Seed 2 test customer accounts for local development. Refuses to run on production.';

    /**
     * @var array<int, array{email:string, name:string, password:string, balance:float}>
     */
    private const CUSTOMERS = [
        ['email' => 'cust@local',  'name' => 'Test Customer',  'password' => 'cust1234', 'balance' => 500.00],
        ['email' => 'cust2@local', 'name' => 'Second Customer','password' => 'cust1234', 'balance' => 0.00],
    ];

    public function handle(): int
    {
        $env = strtolower((string) config('app.env'));
        if ($env === 'production' && ! $this->option('force')) {
            $this->error('[dev:seed-customers] Refusing to run on APP_ENV=production.');
            $this->line('Use --force to override, but you almost certainly do not want that on real infra.');
            return self::FAILURE;
        }

        foreach (self::CUSTOMERS as $c) {
            $user = User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name'                 => $c['name'],
                    'password'             => Hash::make($c['password']),
                    'role'                 => 'user',
                    'balance'              => $c['balance'],
                    'locale'               => 'th',
                    'is_active'            => true,
                    'must_change_password' => false,
                ],
            );
            $this->info("[dev:seed-customers] {$user->email} (id={$user->id}, balance=฿".number_format((float) $user->balance, 2).")");
        }

        $this->line('[dev:seed-customers] Login with password: cust1234');
        return self::SUCCESS;
    }
}
