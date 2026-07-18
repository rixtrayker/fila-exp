# AGENTS.md

## Cursor Cloud specific instructions

This is a **Laravel 10 + Filament 3** pharmaceutical field-force CRM (PHP + MySQL + Vite/Tailwind). Two Filament admin panels are exposed: `/admin` (main, default) and `/app`.

### Stack / tooling (installed in the VM snapshot)
- PHP 8.3 (CLI) with `mbstring, xml, curl, mysql, zip, gd, bcmath, intl, gmp, sqlite3`, Composer 2.
- MySQL 8 (server), Node 22 / npm.
- Dependencies live in `vendor/` (Composer) and `node_modules/` (npm); the update script refreshes both.

### Starting services (not done by the update script — start them each session)
- MySQL is not auto-started on boot: `sudo service mysql start`.
- App server: `php artisan serve --host=0.0.0.0 --port=8000` (serves both panels).
- Frontend dev server: `npm run dev` (Vite on :5173). The `/admin` panel loads its own published Filament assets and works without Vite, but run it for the app's own JS/CSS.
- Admin panel: http://localhost:8000/admin — login `admin@admin.com` / `1234`. All seeded users use password `1234` (see `database/seeders/RolesAndPermissionsSeeder.php`).

### Non-obvious gotchas
- **APP_URL / port:** `AdminPanelProvider` calls `->domain(config('app.url'))`. Laravel strips the `http(s)://` scheme and ignores the port when matching the Host header, so keep `APP_URL=http://localhost` (no port). Serving on `:8000` works and routes resolve. Do NOT put a port in `APP_URL` (e.g. `http://localhost:8000`) — the panel domain becomes `localhost:8000`, which never matches the port-less request host and every panel route 404s.
- **`.env`:** copy `.env.example` → `.env` and run `php artisan key:generate` if `.env` is missing. Defaults (mysql, `root`, empty password, `127.0.0.1:3306`, db `fila`) match the VM's MySQL setup.
- **MySQL routines:** the SQL function/stored-procedure migrations require `log_bin_trust_function_creators=1`; it is persisted in `/etc/mysql/mysql.conf.d/zz-custom.cnf`. Root is configured for TCP on `127.0.0.1` with an empty password.
- **Fresh-DB migration ordering bug:** `client_types` is never seeded by `DatabaseSeeder`, but migration `2026_07_07_000002_run_role_visit_targets_seeder` inserts `role_visit_targets` rows with a FK to `client_types` (PM=1, PH=2, AM=3). On a brand-new/empty database (`php artisan migrate:fresh`, or setting up the `testing` DB) you must insert those three rows before migrating, e.g.:
  `INSERT INTO client_types (id,name,created_at,updated_at) VALUES (1,'PM',NOW(),NOW()),(2,'PH',NOW(),NOW()),(3,'AM',NOW(),NOW());`
  Otherwise migration fails with a 1452 FK error. The snapshot's `fila` DB is already migrated+seeded, so this only bites on a fresh DB.

### Lint / test / build
- Lint: `./vendor/bin/pint --test` (many pre-existing style findings; `./vendor/bin/pint` to auto-fix).
- Tests: `php artisan test --testsuite=Unit` runs the database-free suite (passes). All `Feature` tests plus `tests/Unit/SettingTest.php` use `RefreshDatabase` (`migrate:fresh` on the `testing` DB) and fail on the `client_types` ordering bug above — a known limitation documented in `HANDOVER.md`. Seed `client_types` in the `testing` DB before relying on those.
- Build (prod assets): `npm run build`; dev uses `npm run dev`.
