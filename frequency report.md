## Frequency Report Refactor Plan

- **Scope**: Make the frequency report accurate, consistent, and fast. Keep UI in `Filament`. Decide whether to compute on the fly or via the materialized table `frequency_report_data` (current approach). Align status semantics across the app.
- **Inputs**: Date range `[from_date, to_date]` with optional filters: `brick_id[]`, `grade[]`, `client_type_id[]` (wired in `FrequencyReportResource`).
- **References**: `app/Filament/Resources/FrequencyReportResource.php`, `app/Filament/Resources/DeprecatedFrequencyReportResource.php`, `app/Models/Reports/FrequencyReportData.php`, `app/Jobs/SyncFrequencyReportData.php`, `app/Jobs/FrequencyReportBatchProcess.php`, `app/Filament/Strategies/FrequencyReportStrategy.php`, `app/Livewire/FrequencyReportCell.php`.

### Current behavior (brief)

- `FrequencyReportData` stores per-`client_id`, per-`report_date` facts:
  - `done_visits_count`, `pending_visits_count`, `missed_visits_count`, `total_visits_count`, `achievement_percentage`, `is_final`, `metadata`.
- Aggregation for the grid uses `FrequencyReportData::getAggregatedQuery($from,$to,$filters)` summing those fields, and joining `clients`, `client_types`, `bricks` for labels.
- Data population happens via jobs:
  - `SyncFrequencyReportData` chunks clients and dispatches `FrequencyReportBatchProcess`.
  - `FrequencyReportBatchProcess` loads visits for a client in range, groups by date, and writes rows via `updateOrCreateForDate`.
- Visit breakdown action uses `FrequencyReportStrategy` to show actual visit rows with the same filters.

### Definitions

- **Done visits**: visits with `status = 'visited'`.
- **Pending visits**: visits with `status IN ('pending','planned')`.
- **Missed visits**: visits not completed. Current batch code counts `status = 'cancelled'`; elsewhere the UI/filters also reference `'missed'`. We must unify this.
- **Total visits**: count of all visits considered in the period (excluding soft-deleted).
- **Achievement %**: `SUM(done_visits_count) / NULLIF(SUM(total_visits_count), 0) * 100`, rounded to 2 decimals.

### Issues to fix

- Status mismatch: batch uses `'cancelled'` for missed, while filters/UI also use `'missed'`. Define canonical mapping and enforce it everywhere.
- Soft-deletes: batch does not exclude soft-deleted visits; ensure we filter `deleted_at IS NULL`.
- Data retention: `cleanOldData()` keeps 30 days only; confirm desired retention for reporting windows.
- Label joins: use deterministic label selection (e.g., `MIN(clients.name_en)`) instead of `GROUP_CONCAT`, since we group by `client_id`.
- Indexes: ensure efficient queries on visits and report tables.

### Target behavior

- Single source of truth for status categories:
  - done = `visited`
  - pending = `pending` + `planned`
  - missed = `missed` + `cancelled` (pick one canonical app-level status; map the other)
- Exclude soft-deleted visits from all computations.
- Idempotent daily writes to `frequency_report_data` using `updateOrCreate` per `(client_id, report_date)`.
- Aggregation query returns one row per client with sums and correct `achievement_percentage`.

### SQL-Only Refactor Approach

Following the same pattern as `CoverageReportResource`, we will eliminate all PHP-based data computation and materialized tables, replacing them with pure SQL queries that compute data on-the-fly.

#### New Architecture

1. **Remove dependency on `FrequencyReportData` table** - no more materialized data or batch jobs
2. **Direct SQL computation** - use `fromSub()` with raw SQL like coverage report
3. **Real-time data** - always fresh data without caching delays
4. **Simplified maintenance** - no data sync jobs, no stale data issues

#### New SQL Query Structure

```sql
SELECT
    c.id as client_id,
    c.name_en as client_name,
    ct.name as client_type_name,
    c.grade,
    b.name as brick_name,
    COUNT(CASE WHEN v.status = 'visited' THEN 1 END) as done_visits_count,
    COUNT(CASE WHEN v.status IN ('pending', 'planned') THEN 1 END) as pending_visits_count,
    COUNT(CASE WHEN v.status IN ('missed', 'cancelled') THEN 1 END) as missed_visits_count,
    COUNT(*) as total_visits_count,
    CASE
        WHEN COUNT(*) > 0 
        THEN ROUND((COUNT(CASE WHEN v.status = 'visited' THEN 1 END) / COUNT(*)) * 100, 2)
        ELSE 0.00
    END as achievement_percentage
FROM clients c
JOIN client_types ct ON c.client_type_id = ct.id
JOIN bricks b ON c.brick_id = b.id
LEFT JOIN visits v ON c.id = v.client_id 
    AND DATE(v.visit_date) BETWEEN ? AND ?
    AND v.deleted_at IS NULL
WHERE c.deleted_at IS NULL
GROUP BY c.id, c.name_en, ct.name, c.grade, b.name
HAVING COUNT(*) > 0
```

#### Implementation Changes

1. **Update `FrequencyReportResource::getEloquentQuery()`**:
   - Remove call to `FrequencyReportData::getAggregatedQuery()`
   - Add new `buildFrequencyReportQuery()` method
   - Use `fromSub()` with raw SQL

2. **Create `buildFrequencyReportQuery()` method**:
   - Build filter strings for SQL
   - Construct complete SQL query with proper joins
   - Handle all filter combinations in SQL
   - Return Eloquent builder using `fromSub()`

3. **Remove materialized table dependencies**:
   - Delete `FrequencyReportData` model usage
   - Remove `getAggregatedQuery()` method
   - Eliminate batch processing jobs

4. **Update filters**:
   - All filtering happens in SQL WHERE clauses
   - No PHP-based data manipulation
   - Consistent with coverage report pattern

#### Benefits

- **Performance**: Direct SQL execution, no PHP loops
- **Accuracy**: Always real-time data, no stale materialized data
- **Maintenance**: No batch jobs, no data sync issues
- **Consistency**: Same pattern as coverage report
- **Scalability**: Database handles aggregation efficiently

#### Migration Steps

1. Create new SQL-only query method
2. Update resource to use new method
3. Test with existing filters
4. Remove old materialized table code
5. Update visit breakdown action to use same SQL approach
6. Clean up unused models and jobs

### Formulas (concise)

- `done_visits = COUNT(visits WHERE status = 'visited')`
- `pending_visits = COUNT(visits WHERE status IN ('pending','planned'))`
- `missed_visits = COUNT(visits WHERE status IN ('missed','cancelled'))`
- `total_visits = COUNT(visits)`
- `achievement_percentage = done_visits / NULLIF(total_visits, 0) * 100`

### Data model

Table `frequency_report_data` (materialized per day):
- Keys: `(client_id, report_date)` unique pair
- Metrics: `done_visits_count`, `pending_visits_count`, `missed_visits_count`, `total_visits_count`, `achievement_percentage`
- Flags: `is_final`
- `metadata` JSON for debugging (e.g., per-status breakdown)

### Jobs pipeline

- `SyncFrequencyReportData` determines date range and dispatches `FrequencyReportBatchProcess` in client chunks.
- `FrequencyReportBatchProcess` for each client:
  - Load visits in `[from,to]` with `deleted_at IS NULL`.
  - Collect distinct `visit_date` values.
  - For each date, compute counts by status and write `frequency_report_data`.
  - Set `is_final = (report_date < today())`.

### Aggregation query (grid)

- `FrequencyReportData::getAggregatedQuery($from,$to,$filters)` should:
  - `whereBetween('report_date', [$from,$to])`
  - `groupBy('client_id')`
  - `SUM(...)` metrics and compute `achievement_percentage` from sums
  - Join `clients`, `client_types`, `bricks` for labels
  - Apply filters: `brick_id[]`, `grade[]`, `client_type_id[]`

### Laravel integration

- Resource: `FrequencyReportResource` configures filters and columns and calls `getAggregatedQuery`.
- Deprecated: `DeprecatedFrequencyReportResource` is hidden; use it only as historical reference.
- Visit breakdown: `visit_breakdown` action opens page with `strategy = 'frequency'` and passes current filters.

### Performance and indexing

- Visits: composite index `(client_id, visit_date, status, deleted_at)`; consider `(visit_date, client_id)`.
- Report table: `(client_id, report_date) UNIQUE`, plus index on `report_date` for range scans.
- Avoid `GROUP_CONCAT` in labels; prefer `MIN()` or explicit relationships if needed.

### TODO (ordered)

- [ ] Decide and document canonical "missed" mapping (`missed` vs `cancelled`) and update everywhere (jobs, filters, UI labels).
- [ ] Update batch processing to exclude soft-deleted visits and to use the canonical status mapping.
- [ ] Replace `GROUP_CONCAT` label selection with deterministic fields (e.g., `MIN(clients.name_en)`), or remove labels from SQL and format in PHP.
- [ ] Add/ensure indexes on visits and `frequency_report_data` as above.
- [ ] Confirm retention policy (30 days?) and either keep `cleanOldData()` or adjust.
- [ ] Add a CLI/HTTP trigger to backfill a date range idempotently, with logging.
- [ ] Add automated tests: counts by status, soft-delete exclusion, aggregation math, and filter correctness.
- [ ] Validate numbers for a known week/month for a few clients.

### Notes

- If we later move off the materialized table, we can compute with a single SQL using `SUM(CASE WHEN ...)` over `visits` joined to `clients`, but keep the table for now for UI speed and simpler breakdowns.
