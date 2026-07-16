# Dev CRM Urgent Bug-Fix Handover

Last updated: 2026-07-17 (UTC+3)

## Current status

- Fixes are deployed to the development service only.
- Production was not changed.
- No Git commit or push has been created.
- The requested `HANDOVER.md` was absent from the current branch and server. A prior audit was recovered from commit `980dafa`; this file records the fixes actually deployed and the remaining risks.

## Confirmed and fixed defects

### 1. SOP / visit breakdown failure and missing double visits

- The breakdown queried a nonexistent `visits.second_id` column, causing a MySQL error.
- Several report queries filtered only `user_id`, while the SOP procedure correctly counts both `user_id` and `second_user_id`.
- Participant filters were not consistently grouped with date/client/status filters.
- Added `Visit::participatedBy()` and applied it consistently to the SOP table, statistics, client breakdown, and visit breakdown query.

### 2. Visit report participant filters

- The Visit Report's manager `OR` condition was not grouped, so it could bypass other filters.
- The Visits table ignored the selected manager filter.
- Both filters are now grouped and the manager selection is applied.

### 3. Client search escaped assigned territories

- Arabic-name, phone, and speciality `OR` searches were not grouped with `Client::inMyAreas()`.
- A representative could therefore see/select clients outside assigned bricks.
- The same defect existed in plan client search.
- Search terms are now grouped under the territory constraint.

Dev evidence for the same representative/search:

- Before: 712 results, 347 outside assigned bricks.
- After: 365 results, 0 outside assigned bricks.

### 4. Deployment script silently used incompatible PHP

- Server default CLI: PHP 8.0.30.
- Installed dependencies require PHP 8.2 or newer.
- The old script continued after failed Artisan commands and ended successfully, masking deployment failures.
- The script now uses `/opt/alt/php82/usr/bin/php`, stops on the first failure, and clears the route cache because closure routes cannot be cached.

### 5. Weekly-plan weekday and option failures

- District-manager data used `tue`, `wed`, and `thu`, while the form requested `tues`, `wednes`, and `thurs`.
- The form requested `day-all`, but the client cache only created `day`.
- Carbon date objects were compared directly with strings, leaving all day-specific option lists empty.
- Weekday keys are now canonical and shared, `day-all` is populated, date comparisons are normalized, and manager search stays inside the day's permitted client pool.
- The incorrectly cased `FormBuilder.PHP` was renamed to `FormBuilder.php` for Linux/Composer compatibility.

### 6. Plan shift values and times were discarded

- Form fields used `time_am`/`time_pm`, while persistence expected `am_time`/`pm_time`.
- Time strings were cast to integers.
- Shift parsing was skipped when a day had no general client selection.
- The observer created `plan_shifts` rows without copying any shift values.
- New and edited plans now preserve client IDs and time strings, replace stale shift rows, recreate only that plan's planned visits, and accept legacy weekday keys during editing.
- Existing historical data was not bulk-rewritten: 894 of 972 current `plan_shifts` rows are empty, and rewriting completed plans could destroy visit evidence.

### 7. Additional visit integrity fixes

- A user can no longer accompany themselves on a Double visit.
- Re-saving visit products/samples now replaces the pivot set transactionally instead of inserting duplicate `product_visits` rows.

### 8. Personal-list coverage consistency

- Personal-list fallback is now decided independently for each client type.
- Only active personal-list clients still inside the user's current territory disable area fallback.
- Coverage summaries and all/visited/unvisited client drilldowns now use the same accountable client pool.

## Changed files

- `app/Models/Visit.php`
- `app/Filament/Strategies/SOPsAndCallRateStrategy.php`
- `app/Filament/Resources/VisitResource.php`
- `app/Filament/Resources/VisitResource/Tables/VisitTable.php`
- `app/Filament/Resources/VisitResource/Forms/VisitForm.php`
- `app/Filament/Resources/VisitReportResource.php`
- `app/Filament/Resources/PlanResource/ClientManager.php`
- `app/Filament/Resources/PlanResource/FormBuilder.php`
- `app/Filament/Resources/PlanResource/Pages/CreatePlan.php`
- `app/Filament/Resources/PlanResource/Pages/EditPlan.php`
- `app/Filament/Resources/ClientBreakdownResource.php`
- `app/Models/AccountsCoverageReport.php`
- `app/Models/Client.php`
- `app/Models/Plan.php`
- `app/Observers/PlanObserver.php`
- `app/Services/PlanDataService.php`
- `app/Services/VisitService.php`
- `database/seeders/MigratePlanData.php`
- `scripts/deploy.sh`
- `tests/Unit/PlanDataServiceTest.php`
- `tests/Unit/VisitParticipantScopeTest.php`

## Dev deployment and verification

- Rollback archive:
  `/home/u530702363/backups/dev-crm-20260717-003317.tar.gz`
- Plan-fix rollback archive:
  `/home/u530702363/backups/dev-crm-plan-fix-20260717-004724.tar.gz`
- Coverage-fix rollback archive:
  `/home/u530702363/backups/dev-crm-coverage-fix-20260717-005448.tar.gz`
- Server regression tests: 5 passed.
- Deployment script: completed successfully.
- Migrations: nothing pending.
- Configuration: cached successfully.
- Routes: cache cleared successfully.
- Views: cached successfully.
- Login endpoint: HTTP 200.
- SOP stored procedure: 24 rows, no duplicate users, no visit-count mismatches.
- Fixed SOP client breakdown: loads successfully and includes the accompanied visit used for reproduction.
- Browser smoke tests passed:
  - Dashboard
  - Visits listing
  - Visit Report and participant filters
  - SOPs and Call Rate report
  - SOP breakdown link
  - Weekly Plan create form and all seven day tabs
  - Tuesday, Wednesday, and Thursday shift/time/client controls
  - Accounts Coverage report and client breakdown
- No browser console, SQL, or server errors were observed in those paths.
- Transactional dev checks (all rolled back) passed:
  - Plan create persisted Tuesday clients, shift client, and `09:30` time.
  - Plan edit replaced the shift/visits with Wednesday values and `16:15` time.
  - District-manager Tuesday–Thursday options contained controlled planned clients.
  - Re-saving product samples produced one row per product.
  - PM-only personal lists retained PH area fallback.
- Coverage reconciliation passed:
  - Summary, accountable scope, and drilldown counts matched for PM, PH, and AM.
  - Browser example: total 98 clients and exactly 98 client-breakdown rows.

## Test-suite limitations found

- Targeted database-free tests pass.
- The default database-backed suite was not run because `RefreshDatabase` targets local MySQL database `testing` without proving it is disposable.
- A forced in-memory SQLite unit run had 20 passes and 6 setup failures: `2024_12_19_000000_create_user_bricks_view.php` uses MySQL-only `CREATE OR REPLACE VIEW`.
- Existing PHPUnit data providers emit deprecation notices because they are not static.

## Important follow-up risks

- The development environment reports `APP_ENV=production` with debug mode enabled.
- Default seeded administrator credentials are still accepted on development.
- Several `/admin/ops/*` routes execute sensitive Artisan/system operations without an explicit authorization middleware.
- Rotate the SSH password shared during this incident and the development administrator password.
- Create a dedicated test database (or isolated SQLite-compatible test configuration) before relying on the full suite.
- Remaining correctness work from the recovered audit:
  - Standardize `cancelled` versus nonexistent `missed` visit status and repair stale pending-plan cancellation.
  - Correct dashboard statistics that bypass personal lists/user scope.
  - Correct cross-year and non-annual vacation balance calculations.
  - Review geolocation bypass for clients without coordinates.
  - Correct permission naming/role seeding and add pre-merge CI.
- Personal client lists are mutable and not effective-dated; changing a list can still change historical coverage results. Do not treat historical list-based reports as immutable audit evidence.

## Rollback

From the development project directory, extract the rollback archive over the current files, then run `scripts/deploy.sh`. Do not apply this archive to production.
