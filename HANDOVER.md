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
---

# Full PR #5–#11 Review and Fix Audit

## Objective

Continue the audit and remediation of PRs #5 through #11 in
`rixtrayker/fila-exp`. Use the remote development CRM as a testing playground
before any production rollout.

The repository review found multiple defects that can alter employee
performance, coverage, SOP, sample, and vacation figures. Repository evidence
does not establish that these defects caused any employment decision, so do not
make that claim. However, affected reports must not be treated as reliable until
the relevant fixes and data checks are complete.

## Agreed scope

Fix every issue documented below except:

- **Deferred:** redesigning PR #11 so historical coverage reports use the
  client-list membership that was effective during the selected historical
  period. The current mutable `client_user` pivot cannot represent that history.

Although that redesign is deferred, document in the UI or operational runbook
that historical coverage can change when personal lists change. Do not silently
represent such reports as immutable.

PR #10 is still open and should be corrected before it is merged. PRs #5–#9 and
#11 were merged into `dev`.

## Repository and branch context

- GitHub repository: `https://github.com/rixtrayker/fila-exp`
- Main integration branch for this work: `dev`
- `master` is behind `dev`; create implementation branches from the latest
  `origin/dev`, not from `master`.
- Relevant PRs:
  - #5: SOP client-type target mapping
  - #6: district-manager role-name fix
  - #7: manager selection for double visits
  - #8: per-user vacation entitlement
  - #9: samples distribution report
  - #10: all-activity SOP report and role targets; still open
  - #11: per-user client lists and coverage integration

## Remote development playground

- Host: `31.220.106.100`
- Port: `65002`
- User: `u530702363`
- Expected identity on the operator's machine: `~/.ssh/spyro-pharma`
- Application directory:
  `domains/avantgardepharma.net/public_html/dev-crm/`
- Typical connection:

  ```bash
  ssh -i ~/.ssh/spyro-pharma -p 65002 \
    u530702363@31.220.106.100
  cd domains/avantgardepharma.net/public_html/dev-crm/
  ```

The private key was not available in the original cloud-agent environment.
A password was supplied in the conversation, but it is intentionally not
recorded here. Never commit a password, private key, `.env`, database dump, or
other credential.

Treat this server as a development test target, not as disposable storage:
preserve `.env`, `storage/`, uploaded files, and a database backup before
changing deployment or Git state.

## Confirmed findings

### PR #5 — SOP target mapping migration

The target mapping correction itself was valid: `ClientType` defines PM=1,
PH=2, and AM=3, and the old procedure selected the wrong daily targets.

Defects:

1. **Destructive procedure replacement**
   - File:
     `database/migrations/2026_07_07_000001_fix_sops_procedure_client_type_targets.php`
   - `up()` drops `GetSOPsAndCallRateData` before validating or compiling its
     replacement.
   - The SQL used the unquoted MariaDB reserved alias `offset`; installation
     failed after the drop and left the report procedure unavailable.
   - Commit `ad3888c` later fixed the alias and explicitly confirms the failed
     production migration.
   - Required remediation: make procedure replacement failure-safe. Validate
     the SQL first and retain a known-good definition that can be restored if
     replacement fails.

2. **Destructive rollback**
   - `down()` only drops the procedure; it does not restore the previous
     definition.
   - Required remediation: either restore the prior procedure exactly or make
     the migration explicitly irreversible without destroying the active
     procedure.

### PR #6 — district-manager planning

The role-name correction from `district_manager` to `district-manager` was
correct, but it activated broken day-specific code.

1. **Tuesday–Thursday keys do not match**
   - Files:
     - `app/Filament/Resources/PlanResource/ClientManager.php`
     - `app/Filament/Resources/PlanResource/FormBuilder.PHP`
   - `ClientManager` generates `tue`, `wed`, and `thu` from Carbon's `D`
     format. `FormBuilder` requests `tues`, `wednes`, and `thurs`.
   - Result: district managers receive empty AM, PM, and client options on
     Tuesday through Thursday.
   - Required remediation: centralize canonical weekday keys and use them in
     both classes.

2. **The all-client cache key is never created**
   - The form calls `getClients('all', $day)`, which requests `day-all`.
   - Preparation stores the untyped list under `day`.
   - Searching then falls back to all clients in the area rather than the
     district manager's planned clients.
   - Required remediation: use the untyped `day` key or explicitly create
     `day-all`, and keep search constrained to the same permitted pool.

3. **Related query-scope risk**
   - `searchQuery()` combines a scoped English-name condition with an ungrouped
     `orWhere` for Arabic names.
   - Verify generated SQL so the Arabic-name branch cannot escape
     `inMyAreas()`. Group both name predicates inside one nested condition.

### PR #7 — double-visit accompaniment

1. **Self-accompaniment is accepted**
   - Files:
     - `app/Filament/Resources/VisitResource/Forms/VisitForm.php`
     - `app/Models/User.php`
   - `User::managers()` can include the logged-in manager. The field requires a
     companion for Double calls but does not require
     `second_user_id != user_id`.
   - Required remediation: filter the primary user from options and add
     server-side `different:user_id` validation.

2. **Duplicate SOP rows after double visits**
   - `GetSOPsAndCallRateData.sql` returned one aggregate row from each
     `UNION ALL` branch, causing the outer join to duplicate users present in
     both branches.
   - Commit `0c77b31` fixed this by summing the union per user.
   - Required work: retain the fix and add a regression test covering a user
     with both owned visits and accompanied double visits. This was a latent
     stored-procedure defect surfaced by expanded double-visit usage rather
     than a defect solely created by PR #7.

### PR #8 — vacation entitlement and balance

1. **Cross-year leave is assigned entirely to its start year**
   - File: `app/Filament/Resources/VacationResource.php`
   - Both balance queries use `whereYear(vacation_durations.start, current
     year)` and sum the full duration.
   - Example: December 30 through January 3 is fully charged to the first year
     and contributes nothing to the next year.
   - Required remediation: calculate overlap with the requested calendar year.
     Reuse or extend `VacationCalculator` rather than duplicating date logic.

2. **Every vacation type consumes annual entitlement**
   - The balance queries do not filter `vacation_type_id`.
   - Sick, medical, or other leave therefore reduces
     `annual_vacation_days`.
   - Required remediation: model which vacation types consume annual
     entitlement, migrate existing types with an explicit default, and apply
     that rule consistently in form, table, and report calculations.

3. **Tests required**
   - Custom entitlement.
   - Cross-year and partially overlapping durations.
   - Half-day boundaries.
   - Annual versus non-annual leave.
   - Editing an existing approved request without double counting it.
   - Consistency among request form, request table, and vacation report.

### PR #9 — samples report

1. **Operational roles do not receive the permission**
   - Files:
     - `database/seeders/RolesAndPermissionsSeeder.php`
     - `database/seeders/MedicalRepRole.php`
   - `samples-report` permissions are created but are absent from the
     medical-rep permission map inherited by manager roles.
   - Result: the report is effectively available only to super-admin unless
     permissions are manually granted.
   - Required remediation: add the intended `view` and `view-any`
     permissions to the operational role seeders without removing unrelated
     custom permissions.

2. **Every Livewire row has the same key**
   - File:
     `app/Filament/Resources/SamplesReportResource/Pages/ListSamplesReports.php`
   - `getTableRecordKey()` returns the literal string `id` for every row.
   - Result: Livewire can reuse DOM state for the wrong row after filtering,
     grouping, or pagination.
   - Required remediation: select or construct a stable unique row key. Audit
     copied implementations in the expenses, vacation, and visit reports too.

3. **Names are used as identities**
   - The default group uses `medical_rep` rather than `user_id`.
   - Distinct products are counted by `product_name` instead of product ID.
   - Duplicate names merge separate representatives or products.
   - Required remediation: aggregate/group by IDs while displaying names.

4. **Tests required**
   - Authorization and role seeding.
   - Unique row identity.
   - Duplicate user/product names.
   - Date, rep, and product filters.
   - Table totals versus Excel totals.

### PR #10 — all-activity SOP report; open

Do not merge until these are corrected:

1. **Seeder migration silently skips production**
   - File:
     `database/migrations/2026_07_07_000002_run_role_visit_targets_seeder.php`
   - `Artisan::call('db:seed', ...)` omits `--force`.
   - Laravel refuses the nested seed operation in production while the
     migration can appear successful.
   - Commit `e8a7100` fixed the same problem in older migrations but not this
     open branch.
   - Required remediation: pass `--force => true`, or preferably seed required
     migration data directly and transactionally.

2. **Routine seeding overwrites configured targets**
   - Files:
     - `database/seeders/RoleVisitTargetsSeeder.php`
     - `database/seeders/DatabaseSeeder.php`
   - `updateOrCreate()` resets administrator-edited target values to global
     settings whenever `db:seed` runs.
   - Required remediation: seed missing rows only. Separate initial defaults
     from an explicit reset operation.

3. **Multi-role target selection is nondeterministic**
   - File: `app/Services/AllActivitySOPsReportService.php`
   - `$user->roles->first()` has no defined precedence.
   - Required remediation: define and test an explicit role-priority rule, or
     require selection of the role under which activity is being evaluated.

4. **Existing tests are not run by CI**
   - Preserve and expand
     `tests/Unit/AllActivitySOPsReportServiceTest.php`.
   - Add production-seeding, re-seeding, multi-role, authorization, and export
     coverage.

### PR #11 — personal client lists

The historical effective-dating redesign is deferred, but the following
defects remain in scope.

1. **One pivot row disables fallback for every client type**
   - Files:
     - `app/Models/AccountsCoverageReport.php`
     - `app/Models/Client.php`
   - The `NOT EXISTS` guard checks whether the user has any `client_user` row,
     not whether a row exists for the requested client type.
   - A doctors-only list therefore makes pharmacy and AM reports use an empty
     personal list instead of their area pools. An inactive pivot row also
     disables fallback.
   - This was disclosed as a caveat in the PR but is a report correctness
     defect.
   - Required remediation: decide personal-list fallback independently per
     user and client type, considering only active clients of that type.

2. **Stale territory assignments remain reportable and hidden**
   - Files:
     - `app/Models/AccountsCoverageReport.php`
     - `app/Filament/Resources/MyClientListResource.php`
   - Coverage accepts pivot rows without checking that the client remains in
     the user's current bricks.
   - The UI is scoped to current eligible bricks, so stale rows disappear and
     cannot be removed there.
   - Required remediation: intersect report membership with current eligible
     territory, expose stale memberships to authorized managers, and provide
     an explicit cleanup action rather than silently deleting them.

3. **Summary and drilldowns disagree**
   - Files:
     - `app/Models/AccountsCoverageReport.php`
     - `app/Filament/Resources/ClientBreakdownResource.php`
   - Summary denominators use the personal list, while all/visited/unvisited
     drilldowns still query clients from the user's bricks.
   - Required remediation: route all summary and drilldown queries through one
     accountable-pool implementation.

4. **Dashboard integration was missed**
   - Commit `1e78828` added `Visit::withinAccountablePool()` to the Daily Plan
     Summary after merge.
   - Required work: retain it and test list/fallback behavior. Check other
     widgets and exports for direct area-derived queries.

5. **Missing automated coverage**
   - PR #11 changed no test files despite broad SQL, authorization, export, and
     reporting changes.
   - Add tests for per-type fallback, inactive clients, stale territories,
     summary/drilldown reconciliation, hierarchy authorization, dashboard,
     and exports.

6. **Deferred historical limitation**
   - Do not implement effective dating in this pass.
   - Add an explicit warning/runbook note that changing a list can alter past
     reports, and prohibit using these mutable figures as immutable audit
     evidence.

## Systemic engineering and deployment failures

These safeguards are in scope because they allowed the defects through:

1. PRs #5–#11 had no recorded human reviews.
2. `.github/workflows/deploy-hostinger.yml` listens to closed pull requests and
   pushes; it does not validate an open PR before merge.
3. The successful checks shown on merged PRs were deployments, not test runs.
4. `scripts/deploy.sh` has no fail-fast behavior. A failed migration can be
   followed by a success message and a zero exit status.
5. Deployment installs Composer dependencies with `--no-dev`, so tests are not
   available in the deployed tree.

Required remediation:

- Add pull-request CI for `dev` and `master`.
- Install development dependencies in CI, not on the deployment target.
- Run formatting/static checks, automated tests, and migration validation
  before permitting deployment.
- Separate CI from deployment. Deploy only after CI succeeds.
- Add `set -Eeuo pipefail` to deployment scripts and stop immediately on any
  migration/cache failure.
- Do not claim deployment success unless every command succeeds.
- Capture and retain command output needed to diagnose migration failures.
- Require at least one independent review for report, permission, migration,
  and employee-metric changes.

## Implementation sequence

### 1. Establish a safe baseline

1. Fetch `origin/dev` and create a dedicated fix branch from it.
2. Connect to the development server and record:
   - PHP, Composer, Laravel, database, and MariaDB/MySQL versions.
   - Current commit/build identity if available.
   - `git status` if `.git` exists.
   - Migration status and installed procedure definitions.
3. Back up:
   - `.env` securely outside Git.
   - Database.
   - `storage/` and uploaded files as appropriate.
   - Current application release or a manifest/checksum sufficient to restore
     it.
4. Confirm the server really is the development environment before executing
   migrations or fixture changes.

### 2. Set up Git on the development server safely

The current workflow excludes `.git` during rsync, so the directory may not be
a repository. Prefer a controlled checkout rather than running `git init`
blindly over an unknown deployment:

1. Inspect the existing tree and identify server-only files.
2. Back up `.env`, persistent storage, and web-server entry-point files.
3. Clone or create a worktree of the repository in a sibling release
   directory, checking out `dev` or the test branch.
4. Restore/symlink only approved persistent files into the checkout.
5. Verify ownership and permissions.
6. Atomically repoint the development document root or synchronize the tested
   checkout into `dev-crm/`.
7. Never add `.env`, credentials, database dumps, caches, sessions, logs, or
   uploads to Git.

If hosting constraints require the existing directory itself to become a
checkout, initialize only after the backup and compare its files against
`origin/dev` before setting the branch. Do not overwrite server-only files.

### 3. Fix and test in risk-ordered batches

Use separate logical commits:

1. CI and fail-fast deployment safeguards.
2. PR #5 migration/procedure safety and procedure regression tests.
3. PR #6 weekday/cache/search scoping.
4. PR #7 self-accompaniment and double-visit regression coverage.
5. PR #8 vacation calculation model and tests.
6. PR #9 permissions, stable IDs, grouping, and export reconciliation.
7. PR #11 per-type fallback, stale territory handling, unified drilldowns, and
   integration tests.
8. Correct PR #10 on its own branch or rebase its feature onto the fixed
   integration branch before merging.

### 4. Validate on the development playground

Deploy each batch only after local/CI tests pass. On the development server:

1. Run migrations with full visible output and verify their status.
2. Verify `GetSOPsAndCallRateData` exists and executes after both migration and
   rollback testing on a disposable database.
3. Use controlled test users and clients to exercise:
   - All seven weekdays for district-manager planning.
   - Double visits, including rejection of self-accompaniment.
   - Cross-year, half-day, annual, and non-annual vacations.
   - Samples permissions, duplicate names, filters, totals, and export.
   - Personal lists with doctors only, pharmacies only, inactive clients,
     territory transfers, summary/drilldown links, dashboard, and export.
   - Multi-role users and re-running role-target seeders.
4. Compare displayed totals with direct database queries and exported files.
5. Record anonymized before/after fixtures and expected outcomes.
6. Confirm that failures return nonzero deployment status.

Do not run destructive rollback tests or synthetic fixture creation against
production or against irreplaceable development data.

### 5. Audit affected data

After code behavior is corrected:

1. Determine the deployment windows for PRs #5–#11 and their follow-up fixes.
2. Identify reports generated during those windows.
3. Recalculate SOP, coverage, samples, and vacation results from source data
   using corrected logic.
4. Flag differences for authorized business review.
5. Do not infer employment causation from code alone; preserve an audit trail
   and have the organization reassess decisions using corrected figures.

## Completion criteria

- Every in-scope defect has a regression test.
- PR CI runs before merge and blocks deployment on failure.
- Deployment exits nonzero on migration or cache failure.
- Development-server Git/deployment is reproducible without tracking secrets
  or persistent data.
- Summary, drilldown, and export figures reconcile for controlled fixtures.
- Role seeders are safe to rerun.
- The deferred historical-list limitation is visibly documented.
- Corrected reports have been compared with affected historical outputs and
  discrepancies handed to authorized reviewers.
