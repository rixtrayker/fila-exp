## Frequency Report – Documentation

This document describes how the Frequency Report is built and what each metric means in the current implementation.

### Where it lives

- UI: `App\\Filament\\Resources\\FrequencyReportResource` (page: `ListFrequencyReports`)
- Data source: a single SQL `fromSub(...)` query projected into `App\\Models\\FrequencyReportRow`

### Inputs and filters

- Required date range: `[from_date, to_date]`
  - Defaults: from = 7 days ago; to = today
- Optional filters (planned/partial):
  - `brick_id[]`
  - `client_type_id[]`
  - Current implementation wires only the date range; additional filters can be re-enabled as needed.

### Core calculations (per client)

- Done visits: count of `visits` where `status = 'visited'` in range.
- Pending visits: count of `visits` where `status IN ('planned','pending')` in range.
- Missed visits: count of `visits` where `status = 'missed'` in range.
- Total visits: count of all `visits` for the client in range.
- Achievement %: if `total_visits > 0`, `ROUND(done_visits * 100 / total_visits, 2)`, else `0`.

All visit counts exclude soft-deleted rows (`deleted_at IS NULL`). Clients are shown only when `total_visits > 0` for the selected range.

### Sorting and actions

- Default sort: `client_name ASC`
- Row action “Visit Breakdown”: opens Visits index filtered by the same date range and `client_id` to show underlying rows.

### Implementation references

- Resource: `app/Filament/Resources/FrequencyReportResource.php`
- Related tables: `clients`, `client_types`, `bricks`, `visits`

### Legacy removed

- Materialized table and pipeline removed: `FrequencyReportData`, `SyncFrequencyReportData`, `FrequencyReportBatchProcess`.
- Deprecated Filament resource `DeprecatedFrequencyReportResource` deleted.
- Legacy Livewire widget and blade removed: `FrequencyReportCell` and admin `frequency-report.blade.php`.

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
