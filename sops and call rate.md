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
- Monthly visit target: `working_days * daily_visit_target`.
- Office work count: number of `office_works` for the user within the range.
- Activities count: number of `activities` for the user within the range.
- Actual working days: number of workdays (excluding weekends/holidays) within the range on which the user had at least one of: a visit, an office work entry, or an activity.
- Actual visits: count of `visits` where `(user_id = u OR second_user_id = u)` and `status = 'visited'` within the date range; excludes soft-deleted rows.
- Total visits: count of all `visits` where `(user_id = u OR second_user_id = u)` within the date range; excludes soft-deleted rows.
- Call rate: `ROUND(actual_visits / NULLIF(actual_working_days, 0), 2)` with 0 fallback.
- SOPS: `ROUND(actual_visits / NULLIF(working_days * daily_visit_target, 0), 2)` with 0 fallback.

Notes:
- All date comparisons use `DATE(...)` around timestamps to compare by day.
- Visits tied via either `user_id` or `second_user_id` are included.
- Soft-deleted visits are excluded in all counts.

### Sorting, actions, export

- Default sort: `name ASC`
- Row action “Visit Breakdown”: opens Visits index filtered by the same date range and the selected user to show underlying rows.
- Header action “Export to Excel”: downloads the current query via `SOPsAndCallRateExport`.

### Security

- Visible user set is restricted by `GetMineScope::getUserIds()`; if empty, all users are considered.

### Implementation references

- Resource: `app/Filament/Resources/SOPsAndCallRateResource.php`
- Export: `app/Exports/SOPsAndCallRateExport.php`
- Widget: `app/Filament/Widgets/SOPsAndCallRateWidget.php`
- Related tables: `visits`, `activities`, `office_works`, `official_holidays`, `users`, `areas`, `area_user`, `settings`

### Legacy removed

- Materialized model and jobs have been removed: `SOPsAndCallRateData`, `SOPsAndCallRateProcess`, `SOPsAndCallRateBatchProcess`, and related events/listeners/controllers.
- Deprecated Filament resource `DeprecatedSOPsAndCallRateResource` has been deleted.
