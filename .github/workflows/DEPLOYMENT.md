# Deployment

Deployment to Hostinger is **manual only**. There is no automatic deploy on
push, and the GitHub Actions workflow can no longer release to production.

| Environment | URL | Server path | Database | How it deploys |
|---|---|---|---|---|
| Development | https://dev-crm.avantgardepharma.net | `~/domains/avantgardepharma.net/public_html/dev-crm` | `u530702363_dev_crm` | GitHub Actions (manual dispatch) or by hand |
| Production | https://rep.avantgardepharma.net | `~/domains/avantgardepharma.net/public_html/rep` | `u530702363_fila` | By hand only (see below) |

Server: `31.220.106.100`, SSH port `65002`, user `u530702363`.

> The app root serves 404 by design on both environments — there is no `/`
> route. Use `/admin` to check that a deploy is healthy.

---

## Manual production release

Production has no automated path. Follow these steps in order; do not skip
the backup.

### 0. Preflight

Confirm what you are about to ship, and that prod is currently healthy.

```bash
git log --oneline origin/master -5
curl -s -o /dev/null -w '%{http_code}\n' https://rep.avantgardepharma.net/admin   # expect 200
```

Use the PHP 8.2 binary explicitly for every `artisan` call. The default `php`
on this server is 8.0, which the application does not support:

```bash
PHP_BIN=/opt/alt/php82/usr/bin/php
```

### 1. Back up the database

Always back up before a release, and verify the dump is non-empty before
continuing. A backup that silently failed is worse than none.

```bash
ssh -i ~/.ssh/spyro-pharma -p 65002 u530702363@31.220.106.100
cd ~/domains/avantgardepharma.net/public_html/rep

# Read credentials literally — the password is quoted and contains characters
# that a shell would otherwise expand. Do not `source .env` for this.
DB_USER=$(sed -n 's/^DB_USERNAME=//p' .env | tr -d '"'"'"'' | head -1)
DB_PASS=$(sed -n 's/^DB_PASSWORD=//p' .env | tr -d '"'"'"'' | head -1)
DB_NAME=$(sed -n 's/^DB_DATABASE=//p' .env | tr -d '"'"'"'' | head -1)

mkdir -p ~/backups
OUT=~/backups/prod_$(date +%Y%m%d_%H%M%S).sql.gz
MYSQL_PWD="$DB_PASS" mysqldump --routines --single-transaction \
  -h 127.0.0.1 -u "$DB_USER" "$DB_NAME" | gzip > "$OUT"

# Verify: expect a multi-hundred-KB file and a non-zero table count.
ls -lh "$OUT"
gunzip -c "$OUT" | grep -c '^CREATE TABLE'
```

`--routines` is required. This project keeps logic in stored procedures
(`GetSOPsAndCallRateData` and friends); a dump without it cannot restore them.

### 2. Review what will change

Run rsync as a dry run first, and specifically check what `--delete` would
remove. Never run the real sync until this output looks right.

```bash
rsync -avz --delete --dry-run \
  -e "ssh -i ~/.ssh/spyro-pharma -o StrictHostKeyChecking=no -p 65002" \
  --exclude=".git" --exclude=".github" --exclude="vendor" \
  --exclude="node_modules" --exclude=".env" --exclude=".htaccess" \
  --exclude="index.php" --exclude="storage/logs/*" \
  --exclude="storage/framework/cache/*" --exclude="storage/framework/sessions/*" \
  --exclude="storage/framework/views/*" --exclude="bootstrap/cache/*" \
  --exclude="public/storage" \
  ./ u530702363@31.220.106.100:/home/u530702363/domains/avantgardepharma.net/public_html/rep \
  | grep '^deleting'
```

The exclusions are not optional. `.env`, `.htaccess` and `index.php` are
server-specific and differ from the repository; `vendor/` is installed on the
server and is not shipped.

### 3. Sync

Same command without `--dry-run`.

### 4. Migrate and rebuild caches

`scripts/deploy.sh` on prod now matches the repository version and pins PHP
8.2, so `./scripts/deploy.sh` works. To run the steps by hand instead:

```bash
cd ~/domains/avantgardepharma.net/public_html/rep
PHP_BIN=/opt/alt/php82/usr/bin/php

$PHP_BIN artisan migrate:status | grep Pending   # review before applying
$PHP_BIN artisan migrate --force

$PHP_BIN artisan config:clear && $PHP_BIN artisan config:cache
$PHP_BIN artisan route:clear
$PHP_BIN artisan view:cache
```

Route cache is cleared rather than built: this app has closure-based operations
routes, which cannot be cached.

### 5. Verify

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://rep.avantgardepharma.net/admin   # expect 200
tail -50 storage/logs/laravel-$(date +%Y-%m-%d).log | grep -iE 'ERROR|CRITICAL'
```

If migrations touched a stored procedure, confirm it was reinstalled:

```sql
SELECT ROUTINE_NAME, LAST_ALTERED FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = DATABASE();
```

### Rollback

Code: re-sync from the previous known-good commit (step 2-3).
Database: restore the dump taken in step 1.

```bash
gunzip -c ~/backups/prod_TIMESTAMP.sql.gz \
  | MYSQL_PWD="$DB_PASS" mysql -h 127.0.0.1 -u "$DB_USER" "$DB_NAME"
```

Migrations in this project are not all reversible, so treat the dump — not
`migrate:rollback` — as the real recovery path.

---

## Development release

Dev can be deployed the same way, or through GitHub Actions:

**Actions → "Deploy Laravel to Hostinger (Dev + Tags)" → Run workflow → branch `dev`.**

The workflow is `workflow_dispatch` only. Dispatching it against `master`
fails deliberately — production is not deployable from CI.

The dev server's `scripts/deploy.sh` is current and pins PHP 8.2, so the
one-line form works there:

```bash
cd ~/domains/avantgardepharma.net/public_html/dev-crm && ./scripts/deploy.sh
```

## Tag-based directory sync

Pushing a tag of the form `dir:app,dir:config` syncs only those directories to
`HOSTINGER_CURRENT_PROJECT_PATH`. No migrations, no cache rebuild, no
exclusions. It is a blunt tool for a code-only hotfix — prefer a normal
release.

## CI configuration

Secrets: `HOSTINGER_SSH_KEY`, `SSH_PORT`, `SSH_USER`, `SSH_SERVER`.
Variables: `HOSTINGER_DEV_PROJECT_PATH`, `HOSTINGER_CURRENT_PROJECT_PATH`.

`HOSTINGER_PROD_PROJECT_PATH` is no longer referenced by the workflow. It is
left configured but unused.

## Known issues

- **Production is behind.** As of 2026-08-02 it had 37 pending migrations.
  Review `migrate:status` carefully before the next release; applying that
  backlog in one pass is a significant change and deserves a staging rehearsal.
- **Prod has a stale route cache.** `bootstrap/cache/routes-v7.php` dates from
  2025-10-28, written by an older deploy script that ran `route:cache`. This
  app has closure-based `/admin/ops/*` routes which cannot be cached, so the
  current script runs `route:clear` instead. The existing cache file was left
  in place rather than cleared mid-session; clear it during the next release.
- **`./vendor/bin/phpunit` is not executable** in some checkouts; run tests as
  `php vendor/bin/phpunit`.
