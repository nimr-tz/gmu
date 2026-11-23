# Deployment & Operations — NIMR GMU Portal

Operational notes for running this application. Written during Phase 0 remediation
(22 August 2026); keep it current as the environment changes.

---

## 1. Web server

The application must only ever expose `portal/public`. Everything above it —
`.env`, `composer.json`, `storage/logs`, the whole source tree — must be
unreachable over HTTP.

### Current state (interim)

The app lives inside the XAMPP document root, so Apache serves the project
directory directly. Access is blocked with `.htaccess` files:

| File | Purpose |
|---|---|
| `portal/.htaccess` | `Require all denied` — blocks the entire application directory |
| `portal/public/.htaccess` | `Require all granted` — re-opens only the served directory |
| `portal/storage/app/public/.htaccess` | Denies direct access to uploads and strips script handlers |

Verify after any Apache change:

```bash
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/.env          # expect 403
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/composer.json  # expect 403
curl -o /dev/null -w "%{http_code}\n" http://localhost/GMU/portal/public/login   # expect 200
```

These rules depend on `AllowOverride All` being set for the document root. If
that is ever tightened, the deny rules stop applying and the source is exposed
again — so re-run the checks above after touching Apache configuration.

### Permanent fix (do this before go-live)

Give the application its own virtual host whose `DocumentRoot` is `public`, so
nothing above it is reachable regardless of `.htaccess`:

```apache
# C:\xampp\apache\conf\extra\httpd-vhosts.conf
<VirtualHost *:80>
    ServerName  gmu.nimr.local
    DocumentRoot "C:/xampp/htdocs/GMU/portal/public"

    <Directory "C:/xampp/htdocs/GMU/portal/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog  "logs/gmu-error.log"
    CustomLog "logs/gmu-access.log" combined
</VirtualHost>
```

Add `127.0.0.1 gmu.nimr.local` to `C:\Windows\System32\drivers\etc\hosts`,
set `APP_URL=http://gmu.nimr.local` in `.env`, run `php artisan config:clear`,
and restart Apache. For production, terminate TLS and set
`SESSION_SECURE_COOKIE=true`.

---

## 2. Database

The application connects as `gmu_app@localhost`, which holds privileges on the
`nimr_gmu` schema only — no access to the other databases on this instance, and
no `GRANT`, `FILE` or `SUPER`. The password is in `.env` (not in version
control). Never point the app back at `root`.

To rotate the password:

```sql
ALTER USER 'gmu_app'@'localhost' IDENTIFIED BY '<new password>';
```

then update `DB_PASSWORD` in `.env` and run `php artisan config:clear`.

### MariaDB incident — 22 August 2026

While creating the application user, `GRANT` failed: the Aria system table
`mysql.db` was corrupt and the Aria log file could not be written. A clean
shutdown hung, the process was terminated, the Aria log files were moved aside
(kept in `C:\gmu-backups\aria-logs-20260822\`), and MariaDB rebuilt them on
restart, self-repairing `mysql.db`, `mysql.global_priv` and `mysql.proxies_priv`.
All 16 databases on the instance came back intact and were verified.

Two things still need attention:

- The error log shows recurring InnoDB `log sequence number is in the future`
  warnings, meaning the InnoDB tablespace and its logs disagree — usually the
  result of a data directory copied without its log files, or an unclean
  shutdown. It is not currently fatal, but this instance should be treated as
  fragile until it is rebuilt (dump everything, reinstall MariaDB, reimport).
- Database-level privilege rows were lost during the repair and were recreated
  for `gmu_app` and `pma`. If another application on this machine used a
  database-scoped grant, it will need re-granting.

---

## 3. Backups

`C:\gmu-backups\backup-gmu.ps1` dumps the database, copies `storage/app`, and
prunes to 30 days. It is registered as the scheduled task
`NIMR-GMU-Nightly-Backup`, running daily at 01:00, and logs to
`C:\gmu-backups\backup.log`.

Run on demand:

```powershell
powershell -ExecutionPolicy Bypass -File C:\gmu-backups\backup-gmu.ps1
```

**These copies sit on the same disk as the database.** Copy `C:\gmu-backups`
to another machine or drive on a regular schedule — a disk failure otherwise
takes the database and every backup with it.

Restore:

```bash
C:/xampp/mysql/bin/mysql -u root < C:/gmu-backups/nimr_gmu-<timestamp>.sql
```

Test a restore into a scratch database at least once a quarter. A backup that
has never been restored is not yet a backup.

---

## 4. Accounts

Self-registration is disabled. Accounts are created by the Coordinator at
`/admin/users`. The seeder's default administrator password was rotated on
22 August 2026; new passwords were handed over out of band and should be
changed at first login.

The seeder reads `GMU_ADMIN_PASSWORD`, or generates one and prints it once.
Any account whose password was set by the Coordinator is held at the profile
page until the user chooses their own (audit finding H4, closed).

---

## 5. Scheduler

Deadline reminders and the weekly digest depend on Laravel's scheduler running
every minute. On Windows, register a scheduled task:

```
schtasks /Create /TN "NIMR-GMU-Scheduler" /SC MINUTE /MO 1 ^
  /TR "C:\xampp\php\php.exe C:\xampp\htdocs\GMU\portal\artisan schedule:run" /F
```

`MAIL_MAILER` is currently `log`, so no mail leaves the machine. To change that:

1. Copy the mail settings from `.env.production.example` into `.env` and fill in
   the SMTP host, username and password.
2. `php artisan config:clear`
3. `php artisan gmu:mail-test coordinator@nimr.or.tz` — check the inbox, and the
   spam folder.
4. `php artisan gmu:health` — the mailer line must come back green.

Until that is done `gmu:health` reports it every morning, and reminders still
reach people through in-app notifications (audit finding H8).

### SMS alerts

Slide 10 of the GMU concept asks for alerts that reach a PI in the field.
`App\Services\Sms` holds an Africa's Talking driver and a null driver; the null
driver logs rather than sending, so nothing silently claims delivery. To switch
it on, set in `.env`:

```
GMU_SMS_GATEWAY=africastalking
GMU_SMS_USERNAME=<account username>
GMU_SMS_KEY=<api key>
GMU_SMS_SENDER_ID=NIMR
```

Text messages then go out on the 7-day report window only — the point at which
email alone has clearly not worked — to PIs with a phone number on file.

---

## 6. Daily checks

`php artisan gmu:health` checks the things that fail silently: debug mode left
on, a mailer that writes to a log, uploads sitting on the public disk, the app
connecting as `root`, no active Coordinator, awards that ended without
closeout. It runs every morning at 06:30 and writes to
`storage/logs/health.log`; add `--strict` in a deployment step to make warnings
fail the release.

Scheduled jobs, all in `routes/console.php`:

| Time (EAT) | Command | What it does |
|---|---|---|
| 06:30 | `gmu:health` | Configuration and data checks |
| 07:00 | `gmu:seed-reminders` | Seed grant: reviews, revision responses, concept notes and full proposals due within 3 days; late reviews to the reviewer and GMU |
| 06:45 | `gmu:mark-overdue` | External grants only: flags deliverables past their due date |
| 07:00 | `gmu:send-deadline-reminders` | External grants only: 30/14/7-day reports and compliance, 60/30-day ethics |
| Mon 08:00 | `gmu:send-weekly-digest` | External grants only: the week's movement to the GMU office |

The external-grant jobs run only while `GMU_EXTERNAL_GRANTS` is on.

---

## 7. Database health and the instance rebuild

The 22 August repair (§2) fixed the Aria privilege tables and rebuilt the
`nimr_gmu` schema from a verified dump, so the GMU's own tablespaces are clean.
The InnoDB "log sequence number is in the future" warnings affect the rest of
the instance and have not been cleared — that needs a maintenance window,
because the same MariaDB serves `website`, `nimr_journal`, `nreims`,
`nimr_intranet`, `ajsc_db`, `eqrs_db`, `tmsc_db` and others.

### Verifying a backup restores

Done for `nimr_gmu` on 22 August; repeat quarterly, and for any database before
you rebuild it:

```bash
mysqldump -u root --single-transaction --routines --triggers --databases <db> > dump.sql
mysql -u root -e "CREATE DATABASE <db>_verify"
sed 's/`<db>`/`<db>_verify`/g' dump.sql | mysql -u root
# compare table and row counts, then drop the verify database
```

### Rebuilding one database

This is what cleared `nimr_gmu`. Per database, with users off the system:

```bash
mysqldump -u root --single-transaction --routines --triggers --databases <db> > <db>.sql
# verify the dump restores (above) BEFORE the next line
mysql -u root -e "DROP DATABASE <db>; CREATE DATABASE <db> CHARACTER SET utf8mb4"
mysql -u root <db> < <db>.sql
# database-level grants are dropped with the database — re-grant afterwards
mysql -u root -e "GRANT ... ON <db>.* TO '<user>'@'localhost'; FLUSH PRIVILEGES"
```

### If the instance keeps degrading

The durable fix is a clean MariaDB installation: dump every database, note the
grants (`SHOW GRANTS` per user), reinstall, reimport, re-grant. Budget an
evening, and take the dumps to another machine first — the current backups sit
on the same disk as the data they protect.

---

## 8. Release checklist

- [ ] `php artisan config:clear && php artisan route:clear && php artisan view:clear`
- [ ] `npm run build` if front-end assets changed
- [ ] `php artisan migrate --force`
- [ ] Backup taken before migrating
- [ ] Exposure checks in §1 re-run
- [ ] `APP_DEBUG=false` and `APP_ENV=production` in any shared environment
- [ ] `php artisan gmu:health --strict` passes
- [ ] `php artisan test` passes
