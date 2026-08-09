#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Toggles honored by supervisord.conf
export RUN_QUEUE_WORKER="${RUN_QUEUE_WORKER:-true}"
export RUN_SCHEDULER="${RUN_SCHEDULER:-true}"

# Ensure writable directories exist (bind-mounted volumes can start empty)
mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    bootstrap/cache

chown -R app:app storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

# APP_KEY is required. Generate an ephemeral one if missing so first boot doesn't
# crash, but log loudly — production should provide a stable key via env.
if [ -z "${APP_KEY:-}" ]; then
    echo "[entrypoint] WARNING: APP_KEY is empty. Generating an ephemeral key."
    echo "[entrypoint]          Set APP_KEY in Coolify to keep sessions across deploys."
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY
fi

# Wait for the database when DB_HOST is a networked host (skip for sqlite)
if [ "${DB_CONNECTION:-mysql}" != "sqlite" ] && [ -n "${DB_HOST:-}" ]; then
    echo "[entrypoint] Waiting for database at ${DB_HOST}:${DB_PORT:-3306}..."
    for i in $(seq 1 60); do
        if php -r "exit(@fsockopen('${DB_HOST}', (int)(getenv('DB_PORT') ?: 3306)) ? 0 : 1);"; then
            echo "[entrypoint] Database is reachable."
            break
        fi
        if [ "$i" -eq 60 ]; then
            echo "[entrypoint] Database not reachable after 60s. Continuing anyway."
        fi
        sleep 1
    done
fi

# Run migrations unless explicitly disabled
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Running migrations..."
    php artisan migrate --force --no-interaction || \
        echo "[entrypoint] Migrations failed — continuing so the app can still serve."
fi

# This project ships raw SQL in database/schema.sql instead of Laravel migrations.
# Bootstrap the schema on first boot when the app tables don't exist yet.
# Detection: does the `users` table (created by schema.sql) exist?
#
# We use PHP/PDO for the import — the Alpine mariadb client can't speak
# MySQL 8 caching_sha2_password. PDO uses the same driver Laravel does.
if [ "${RUN_SCHEMA_BOOTSTRAP:-true}" = "true" ] \
    && [ "${DB_CONNECTION:-mysql}" = "mysql" ] \
    && [ -n "${DB_HOST:-}" ] \
    && [ -f database/schema.sql ]; then

    HAS_USERS=$(php -r '
        try {
            $pdo = new PDO(
                "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD"),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $r = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=\"users\"")->fetchColumn();
            echo (int)$r;
        } catch (Throwable $e) { echo "0"; }
    ' 2>/dev/null || echo "0")

    if [ "${HAS_USERS}" = "0" ]; then
        for sql in database/schema.sql database/topup_migration.sql docker/laravel-support.sql; do
            [ -f "$sql" ] || continue
            echo "[entrypoint] Importing $sql ..."
            SQL_FILE="$sql" php -r '
                $file = getenv("SQL_FILE");
                try {
                    $pdo = new PDO(
                        "mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306).";dbname=".getenv("DB_DATABASE"),
                        getenv("DB_USERNAME"),
                        getenv("DB_PASSWORD"),
                        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                    );
                    $sql = file_get_contents($file);
                    // Strip line comments; preserve string contents.
                    $sql = preg_replace("/^\s*--.*$/m", "", $sql);
                    // Split on semicolons at line end, ignoring empties.
                    foreach (preg_split("/;\s*(\r?\n|$)/", $sql) as $stmt) {
                        $stmt = trim($stmt);
                        if ($stmt === "") continue;
                        $pdo->exec($stmt);
                    }
                    fwrite(STDERR, "[entrypoint]   OK: $file\n");
                } catch (Throwable $e) {
                    fwrite(STDERR, "[entrypoint]   FAILED: $file — ".$e->getMessage()."\n");
                    exit(1);
                }
            ' || echo "[entrypoint] Import of $sql failed — continuing."
        done
    else
        echo "[entrypoint] Schema already present — skipping SQL bootstrap."
    fi
fi

# Cache config / routes / views / events for production
if [ "${APP_ENV:-production}" != "local" ]; then
    echo "[entrypoint] Caching config, routes, views..."
    php artisan config:cache   || true
    php artisan route:cache    || true
    php artisan view:cache     || true
    php artisan event:cache    || true
fi

# Public storage symlink (idempotent)
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link || true
fi

# Make sure caches written as root are readable by php-fpm workers (app user)
chown -R app:app storage bootstrap/cache

exec "$@"
