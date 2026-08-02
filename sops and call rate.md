## SOPs And Call Rate – Documentation

This document describes how the SOPs And Call Rate report is built and what each metric means in the current implementation.

### Where it lives

- UI: `App\Filament\Resources\SOPsAndCallRateResource` (page: `ListSOPsAndCallRates`)
- Data source: stored procedure `GetSOPsAndCallRateData` projected into `App\Models\SOPsAndCallRate`
- Export: `App\Exports\SOPsAndCallRateExport` (Excel)
- Widget (dashboard summary): `App\Filament\Widgets\MonthlyVisitStatsWidget`

### Inputs and filters

- Required date range: `[from_date, to_date]`
  - Defaults: from = first day of current month; to = today
- Optional filters:
  - `user_id[]` (multiple) – restricted by `GetMineScope::getUserIds()`
  - `client_type_id` (single) – used to determine daily target (AM / PM / PH)

### Core calculations (per user)

- Working days: count of calendar days in range excluding Saturdays and Sundays and any `official_holidays`.
- Daily visit target: value from `settings` table, key chosen by client type:
  - AM → `daily_am_target` (default 2)
  - PM → `daily_pm_target` (default 6)
  - PH → `daily_ph_target` (default 8)
- Monthly visit target: `actual_working_days * daily_visit_target`.
- Office work count: number of distinct dates with `office_works` for the user within the range where `status = 'approved'`.
- Activities count: number of distinct dates with `activities` for the user within the range.
- Actual working days: number of workdays (excluding weekends/holidays) within the range on which the user had NO office work, activities, or vacations. This is calculated as:
  ```
  actual_working_days = working_days - distinct(
      approved_office_work_dates + 
      activity_dates + 
      approved_vacation_dates
  )
  ```
- Actual visits: count of `visits` where `(user_id = u OR second_user_id = u)` and `status = 'visited'` within the date range; excludes soft-deleted rows and visits outside the credited user's accountable client pool (see below).
- Total visits: count of all `visits` where `(user_id = u OR second_user_id = u)` within the date range; excludes soft-deleted rows and visits outside the credited user's accountable client pool (see below).

#### Accountable client pool

Visits only count toward a user when the visited client is one that user is accountable for:

- If the user maintains a personal client list (`client_user`) **for the visited client's type**, only clients on that list count.
- Otherwise the area-derived pool applies: the client must sit in one of the user's bricks (`user_bricks_view`).
- Inactive clients (`active = 0`) never count, and list entries pointing at inactive or out-of-territory clients are ignored when deciding whether a list is maintained.

The list is evaluated per client type, so a user who curates only their pharmacy list is still measured against the area pool for AM and PM. On a double visit each participant is judged against their own list. This is the same rule used by the accounts coverage report (`Client::scopeAccountablePool`, `Visit::scopeWithinAccountablePool`).
- Call rate: `ROUND(actual_visits / NULLIF(actual_working_days, 0), 2)` with 0 fallback.
- SOPS: `ROUND(actual_visits / NULLIF(actual_working_days * daily_visit_target, 0) * 100, 2)` with 0 fallback.

Notes:
- All date comparisons use `DATE(...)` around timestamps to compare by day.
- Visits tied via either `user_id` or `second_user_id` are included.
- Soft-deleted visits are excluded in all counts.
- Office work is only counted when `status = 'approved'`.
- Office work dates are based on `created_at` field, not `time_from`.

### Sorting, actions, export

- Default sort: `name ASC`
- Row action "Visit Breakdown": opens Visits index filtered by the same date range and the selected user to show underlying rows.
- Header action "Export to Excel": downloads the current query via `SOPsAndCallRateExport`.

### Security

- Visible user set is restricted by `GetMineScope::getUserIds()`; if empty, all users are considered.

### Implementation references

- Resource: `app/Filament/Resources/SOPsAndCallRateResource.php`
- Export: `app/Exports/SOPsAndCallRateExport.php`
- Widget: `app/Filament/Widgets/SOPsAndCallRateWidget.php`
- Related tables: `visits`, `activities`, `office_works`, `official_holidays`, `users`, `areas`, `area_user`, `settings`, `clients`, `client_user`, `user_bricks_view`

### Legacy removed

- Materialized model and jobs have been removed: `SOPsAndCallRateData`, `SOPsAndCallRateProcess`, `SOPsAndCallRateBatchProcess`, and related events/listeners/controllers.
- Deprecated Filament resource `DeprecatedSOPsAndCallRateResource` has been deleted.
