# Deploying to Coolify

This app ships as a single container: `nginx` + `php-fpm` + `supervisord`
running a queue worker and scheduler alongside the web server. The image is
built by the repo `Dockerfile` (multi-stage: Vite -> Composer -> runtime).

## 1. Rotate secrets first (once)

The tracked `.env` currently holds real credentials. Before anything reaches a
public host, rotate:

- Google OAuth client secret
- Facebook OAuth client secret
- Gmail app password (`MAIL_PASSWORD`)
- Omise keys
- iFreeiCloud API key

The `.env` is gitignored and `.dockerignore` excludes it from the image, so it
will not be baked in — but it lives in plaintext on disk, so treat those values
as compromised.

## 2. Create the app in Coolify

1. **New Resource -> Application -> Dockerfile**.
2. Point it at this repo / branch. Coolify will detect the `Dockerfile` at the
   root.
3. Under **Build**, no extra args needed.
4. Under **Network**, set the exposed port to **8080** (matches the `EXPOSE`
   and nginx `listen`).
5. Attach your domain and enable HTTPS — Traefik in front of Coolify will
   terminate TLS and forward to `:8080` over HTTP.

## 3. Attach a database

**New Resource -> Database -> MySQL** (or MariaDB). Coolify exposes it on the
internal Docker network at the service name you pick (e.g. `mysql`). Copy the
generated credentials into the app env vars below.

If you want Redis for cache/session/queue, add **Database -> Redis** the same
way and swap the driver env vars (`CACHE_STORE=redis`, etc.).

## 4. Set environment variables

Copy from `.env.coolify.example` into the Coolify app's **Environment
Variables** panel. Minimum required for a healthy boot:

| Variable      | Notes                                                     |
| ------------- | --------------------------------------------------------- |
| `APP_KEY`     | Generate once with `php artisan key:generate --show`.     |
| `APP_URL`     | Full https URL of the domain you attached.                |
| `DB_*`        | Matches the Coolify MySQL service.                        |
| OAuth secrets | Google / Facebook — rotated values, not the ones in .env. |
| `MAIL_*`      | SMTP creds (rotated Gmail app password if still Gmail).   |

Runtime knobs:

- `RUN_MIGRATIONS=true` — run `artisan migrate --force` on boot (default on).
- `RUN_QUEUE_WORKER=true` — supervisord starts `queue:work`.
- `RUN_SCHEDULER=true` — supervisord ticks `schedule:run` once a minute.

Set `RUN_QUEUE_WORKER=false` / `RUN_SCHEDULER=false` if you scale to multiple
replicas and want a dedicated worker container instead.

## 5. Persistent storage

Add a Coolify **Persistent Volume** mounted at:

```
/var/www/html/storage
```

This keeps uploaded files, logs, and the session/cache/view directories across
deploys. `public/storage` is a symlink created at boot by the entrypoint.

## 6. Health check

The image ships with a healthcheck that hits `/up` (Laravel's built-in health
endpoint). Coolify will show container health once the first deploy is green.

## 7. Deploy

Trigger deploy in Coolify. First boot the entrypoint will:

1. Ensure `storage/` and `bootstrap/cache/` are writable.
2. Wait for the database (up to 60s).
3. Run `php artisan migrate --force`.
4. Cache config / routes / views / events.
5. Ensure the `public/storage` symlink exists.
6. Start `php-fpm`, `nginx`, `queue:work`, `schedule:run` under supervisord.

## Local smoke test

```bash
docker compose build
docker compose up
# App on http://localhost:8080
# MySQL on localhost:3307
```

The compose file mirrors the Coolify runtime settings so you can catch
configuration issues before pushing.

## Scaling notes

- Multiple app replicas: set `RUN_QUEUE_WORKER=false` and `RUN_SCHEDULER=false`
  on the web replicas, then deploy a second Coolify app from the same image
  with only the worker/scheduler enabled (or run a separate `docker` service).
  Otherwise every replica will fire the scheduler every minute (duplicate
  jobs).
- Shared storage: for >1 replica you need a shared volume (NFS/S3) or move
  uploads to object storage; per-container volumes will drift.

## Rebuilding front-end assets

Vite runs during `docker build` (stage `assets`). Any change to
`resources/`, `vite.config.js`, `tailwind.config.js`, or `package.json`
triggers a rebuild on the next Coolify deploy.
