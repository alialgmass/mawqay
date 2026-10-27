# Deployment guide

How to stand up and operate Mawqaay on a server. Written for the pilot (2–3 Vendors); revisit before wider launch.

## Requirements

- PHP 8.3 or newer with the `gd`, `intl`, `mbstring`, `pdo_mysql` (or `pdo_pgsql`), `openssl`, `fileinfo`, `zip` and `redis` extensions.
- MySQL 8 or PostgreSQL 15 (production must not use SQLite: the Ledger and Audit append-only triggers are created for MySQL and PostgreSQL).
- Redis for cache, sessions and queues.
- Node 20+ only on the build machine, not the server, if you build assets elsewhere.
- A process supervisor (Supervisor or systemd) and cron.
- HTTPS in front of the app (the app sends transport-security headers only over HTTPS in production).

## Environment

Start from `.env.example`. These matter most in production:

| Variable | Production value |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | the public HTTPS address |
| `APP_KEY` | generated once with `php artisan key:generate --show`, then never changed (it encrypts payout details and admin secrets) |
| `DB_*` | MySQL or PostgreSQL connection |
| `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION` | `redis` |
| `FILESYSTEM_DISK`, `PAYMENTS_DISK` | a **private** disk (`local` outside the web root, or a private bucket). Never `public` |
| `ADMIN_MFA_REQUIRED` | `true` |
| `TELESCOPE_ENABLED` | `false` |
| `MAIL_*` | a real mail provider, so invitations, surveys and notifications are delivered |
| `LOG_LEVEL` | `info` |

Losing or changing `APP_KEY` makes every encrypted value (payout destinations, admin authenticator secrets) unreadable.

## First run

1. Provision the server and database; create the `.env`.
2. `composer install --no-dev --optimize-autoloader`
3. Build assets (`npm ci && npm run build && npm run build:filament`) and upload `public/build`.
4. `php artisan migrate --force`
5. `php artisan db:seed --class=Modules\\Core\\Database\\Seeders\\PlatformSettingsSeeder --force` to create the Platform Settings.
6. Create the first Platform Admin account (never use the demo seeder in production, it does not run there).
7. `php artisan theme:register default` (and any other Theme), then `php artisan storage:link` if you serve public Store images from the public disk.
8. `php artisan config:cache route:cache view:cache event:cache`
9. Start the queue worker and add the scheduler (below).
10. Sign in as the admin, set up the authenticator app when prompted, and review **Platform Settings** (Commission default, return window, survey delay, minimum payout).
11. Run the **first-run checklist** at the end of this document.

## Queue and scheduler

Notifications, invitations and image work run on the queue. The scheduler runs expiry, funds release, surveys, Reconciliation and the heartbeat.

Cron (one line):

```cron
* * * * * cd /var/www/mawqaay && php artisan schedule:run >> /dev/null 2>&1
```

Supervisor program for the worker:

```ini
[program:mawqaay-worker]
command=php /var/www/mawqaay/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
process_name=%(program_name)s_%(process_num)02d
numprocs=2
autostart=true
autorestart=true
stopwaitsecs=3600
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/mawqaay-worker.log
```

Scheduled commands (for reference): `orders:expire` every 5 minutes, `ledger:release-funds` hourly, `feedback:send-surveys` hourly, `ledger:reconcile` daily, `system:heartbeat` every minute.

## Zero-downtime deploy

Use release directories with a `current` symlink and a shared `storage` and `.env`:

```bash
#!/usr/bin/env bash
set -euo pipefail
APP=/var/www/mawqaay
RELEASE=$APP/releases/$(date +%Y%m%d%H%M%S)

git clone --depth 1 --branch main <repository> "$RELEASE"
cd "$RELEASE"
ln -s "$APP/shared/.env" .env
rm -rf storage && ln -s "$APP/shared/storage" storage

composer install --no-dev --optimize-autoloader
# Built assets are uploaded to public/build by the build pipeline before this step.

php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache

ln -sfn "$RELEASE" "$APP/current"
php artisan queue:restart
sudo supervisorctl restart mawqaay-worker:*
```

Migrations must be backwards compatible with the previous release for the few seconds both run. Never drop a column in the same release that stops using it.

## Rollback

1. Point `current` at the previous release directory: `ln -sfn <previous> /var/www/mawqaay/current`.
2. `php artisan queue:restart`.
3. If the release ran a migration that must be undone, run `php artisan migrate:rollback --step=<n> --force` from the previous release only if its `down()` methods are safe; otherwise restore the database backup taken before the deploy.
4. Confirm `/health` and the **System health** page in the Admin Panel.

Take a database backup immediately before every deploy.

## Backups and restore

The system holds money records, so back up the **database** and the **private files disk** (Receipts and payout proofs) every day, keep at least 14 days, and copy them off the server.

No backup package is installed by default (adding one is a dependency decision). Until one is approved, use the database's own tooling on a schedule:

```bash
# MySQL example, run daily from cron, then copy off-server
mysqldump --single-transaction --routines --triggers mawqaay | gzip > /backups/mawqaay-$(date +%F).sql.gz
tar -czf /backups/mawqaay-files-$(date +%F).tar.gz -C /var/www/mawqaay/shared storage/app
```

`--triggers` matters: the append-only triggers on Ledger and Audit entries are part of the schema.

**Restore rehearsal (do this once before launch, and after any change to the process):**

1. On a clean machine, create an empty database and restore the dump.
2. Restore the files archive to `storage/app`.
3. Point a copy of the app at it with the production `APP_KEY`.
4. Run `php artisan ledger:reconcile`; it must report no differences.
5. Open a Receipt and a payout proof from the Admin Panel to confirm the files are intact.
6. Record the date, the time it took and who did it.

## Monitoring

- Point an uptime monitor at `GET /health` (returns `{"status":"ok"}` or 503 when the database does not answer).
- The Admin Panel **System health** page shows queue backlog, failed jobs, the last scheduler run and the last Reconciliation; check it daily during the pilot.
- Errors: until an error tracker is approved, watch the application log (`storage/logs`) and the failed-jobs table.
- Financial events (payment decisions, refunds, payouts, forced changes) are in the **Audit log** page.

## Security checklist before opening to real Customers

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, HTTPS only.
- [ ] Every Platform Admin has set up the authenticator app.
- [ ] `TELESCOPE_ENABLED=false`.
- [ ] Receipts and proofs are on a private disk; a Receipt address from outside the Admin Panel returns 403.
- [ ] Database and files backups run, and the restore was rehearsed.
- [ ] Queue worker and cron are running (System health shows a recent scheduler run).
- [ ] Mail is delivered to a real inbox.

## First-run checklist

- [ ] Admin signs in with the second factor.
- [ ] Platform Settings reviewed.
- [ ] A Payment Method (Vodafone Cash or InstaPay account) is configured.
- [ ] A test Vendor is invited and signs in.
- [ ] A test Order goes through checkout, Receipt upload and approval.
- [ ] `php artisan ledger:reconcile` reports no differences.
- [ ] System health shows all checks healthy.
