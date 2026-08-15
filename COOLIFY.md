# Deploying to Coolify

Single-container deploy: `nginx` + `php-fpm` + `supervisord` (queue worker
+ scheduler) built from the repo `Dockerfile` (multi-stage: Vite → Composer
→ runtime).

Every migration is idempotent and re-runs on every boot, so **redeploys are
safe** — the entrypoint reconciles schema, admin accounts, and caches on
each start.

---

## 1. Rotate any credentials that have ever been in this repo

If any secret was pasted in chat / commits / your local `.env`, treat it as
compromised. Rotate before touching Coolify:

- iFreeICloud API key (at the iFreeICloud dashboard)
- Google OAuth client secret
- Facebook OAuth client secret
- Gmail app password (`MAIL_PASSWORD`)

`.env` is gitignored and excluded from the Docker image, but a leaked
plaintext value on your dev machine is still leaked.

---

## 2. Create the Coolify resources

**Application → Dockerfile → point at this repo/branch.**

- Port: **8080** (matches `EXPOSE` + nginx `listen`)
- Attach your domain + enable HTTPS (Traefik terminates TLS)

**Database → MySQL 8.**

- Note the internal service name (default: `mysql`)
- Copy the generated user/password into the env vars below

Optional: **Database → Redis** if you want to scale beyond database-driven
cache/session/queue. Then switch the driver env vars accordingly.

---

## 3. Environment variables

Copy from `.env.coolify.example` into the Coolify **Environment Variables**
panel. **Required for a healthy boot:**

| Variable | Value | Why |
|---|---|---|
| `APP_KEY` | `base64:...` — generate with `php artisan key:generate --show` | Session/cookie encryption. Entrypoint **hard-fails** on prod without one. |
| `APP_ENV` | `production` | Locks the debug + key gates. |
| `APP_DEBUG` | `false` | Entrypoint **hard-fails** if true when APP_ENV=production. |
| `APP_URL` | `https://your-domain.example.com` | Absolute URLs in emails, OAuth callbacks. |
| `APP_TIMEZONE` | `Asia/Bangkok` | Timestamps render in Bangkok time. Vendor default is UTC. |
| `APP_LOCALE` | `th` | Thai UI by default. |
| `LOG_CHANNEL` | `stderr` | Sends logs to Docker stdout → visible in Coolify. |
| `LOG_LEVEL` | `info` | |
| `DB_HOST` | `mysql` (or whatever you named the DB service) | |
| `DB_DATABASE` | `icloud_checker` | |
| `DB_USERNAME` | (from Coolify MySQL) | |
| `DB_PASSWORD` | (from Coolify MySQL) | |
| `CACHE_STORE` | `database` (or `redis`) | |
| `SESSION_DRIVER` | `database` (or `redis`) | |
| `QUEUE_CONNECTION` | `database` (or `redis`) | |
| `TRUSTED_PROXIES` | `*` | Coolify sits behind Traefik. |
| `IFREEICLOUD_KEY` | (rotated key from iFreeICloud) | Provider API. |
| `IFREEICLOUD_USD_TO_THB` | `36.00` | Adjust per current rate. |
| `IFREEICLOUD_DEFAULT_MARKUP` | `2.5` | Sell = cost × this. |
| `ADMIN_EMAIL` | your real admin email | Primary admin provisioned on first boot. |
| `ADMIN_NAME` | `Admin` | |
| `ADMIN_PASSWORD` | (blank to auto-generate + log once, or set explicitly) | Forced-change on first login regardless. |

**Optional but recommended:**

| Variable | Notes |
|---|---|
| `ADMIN2_EMAIL`, `ADMIN2_NAME`, `ADMIN2_PASSWORD` | Second admin (leave email empty to skip). Same forced-change flow. |
| `MAIL_*` | SMTP creds for low-balance / topup notifications. |
| `GOOGLE_CLIENT_*`, `FACEBOOK_CLIENT_*` | Only if you enable social login. |
| `RUN_QUEUE_WORKER` | Default `true`. Set to `false` on web replicas if scaling out. |
| `RUN_SCHEDULER` | Same as above. |
| `RUN_MIGRATIONS` | Default `true`. Set `false` only if you want manual control. |
| `RUN_ADMIN_ENSURE` | Default `true`. Set `false` to skip admin provisioning. |

---

## 4. Persistent storage

Attach a Coolify persistent volume at:

```
/var/www/html/storage
```

This keeps:
- Uploaded bank-transfer slips (`storage/app/public/topup-slips/`)
- Application logs
- Session / cache / view compilation

`public/storage` is a symlink created by the entrypoint on every boot.

---

## 5. IP allowlist at iFreeICloud

iFreeICloud has an **IP Guard** feature that blocks calls from unlisted IPs.
Symptom: every check fails with `Blocked by IP Guard - Unauthorized IP`.

Two options:

- **A.** In your iFreeICloud dashboard, add your Coolify server's outbound IP.
  Get it from a Coolify shell:
  ```
  curl -s https://api.ipify.org
  ```
- **B.** Disable IP Guard entirely (simplest; safe for this app because
  compromise only wastes provider credits — no money movement).

---

## 6. Deploy

Trigger deploy in Coolify. The entrypoint will:

1. Refuse to start if `APP_ENV=production` and `APP_KEY` is missing.
2. Refuse to start if `APP_ENV=production` and `APP_DEBUG=true`.
3. Ensure `storage/` and `bootstrap/cache/` are writable.
4. Wait for the database (60s).
5. Run `php artisan migrate --force`.
6. **First boot only:** import `database/schema.sql`, `topup_migration.sql`,
   `docker/laravel-support.sql` (Laravel framework tables).
7. **Every boot:** apply idempotent migrations:
   - `database/bank_transfer_migration.sql`
   - `database/services_importer_migration.sql`
   - `database/order_extra_fields_migration.sql`
8. Run `php artisan admin:ensure` — provisions ADMIN + ADMIN2 if not
   already in the DB.
9. Cache config / routes / views / events.
10. Ensure `public/storage` symlink.
11. Start php-fpm, nginx, queue:work, schedule:run under supervisord.

---

## 7. Post-deploy checklist

- [ ] `curl -sI https://your-domain/up` returns `200`
- [ ] Log in as `ADMIN_EMAIL`. If `ADMIN_PASSWORD` was blank, grab the
      generated password from the deploy log (once). You'll be forced to
      change it on first login.
- [ ] Admin sidebar → **Services** → confirm the four seeded services are
      present. Deactivate #316 (MacBook All-in-One) — it's seeded inactive
      because the provider rejects Mac serials on that ID.
- [ ] Admin sidebar → **Bank Accounts** → add at least one active bank
      account so customers have a topup destination.
- [ ] Admin sidebar → **Topup Review** — verify the pending count badge
      renders (should be 0 on a fresh deploy).
- [ ] Test a check with a real serial to confirm the iFreeICloud key +
      IP allowlist are working.
- [ ] Set up Coolify **backups** on the MySQL service (daily minimum).
- [ ] Verify times in `/admin/orders` render as Bangkok local time, not UTC.

---

## 8. Scaling notes

- **Multiple app replicas:** set `RUN_QUEUE_WORKER=false` and
  `RUN_SCHEDULER=false` on the web replicas. Run one dedicated worker
  container with them `true`. Otherwise every replica fires the scheduler
  once a minute → duplicate low-balance emails etc.
- **Shared storage:** with >1 replica you need shared `/var/www/html/storage`
  (NFS or S3-mounted), or slip uploads become per-container.

---

## 9. Local smoke test (mirrors prod posture)

```bash
docker compose down -v          # start fresh
export APP_KEY="base64:$(openssl rand -base64 32)"
export ADMIN_EMAIL=admin@example.com
export ADMIN_PASSWORD=admin1234    # or leave blank to auto-generate
docker compose up -d
docker compose logs -f app
```

Then:
```bash
# Seed 2 test customer accounts (refuses to run in production):
docker compose exec app php artisan dev:seed-customers
```

Login credentials for the two seeded customers:
- `cust@local`   / `cust1234` — starting balance ฿500
- `cust2@local`  / `cust1234` — starting balance ฿0

---

## 10. Rebuilding front-end assets

Vite runs during `docker build` (stage `assets`). Any change to
`resources/`, `vite.config.js`, `tailwind.config.js`, or `package.json`
triggers a rebuild on the next Coolify deploy.
