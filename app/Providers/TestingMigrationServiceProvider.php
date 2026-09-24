<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Registers the test-only migration path. Production provisions the database
 * from database/schema.sql (no Laravel migrations), so the framework tables
 * the test suite needs live in database/testing-migrations. This provider is
 * registered from bootstrap/app.php only when the app is running in the
 * testing environment, keeping production's migrate step a no-op.
 */
class TestingMigrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('testing-migrations'));
    }
}
