## Coverage Report Refactor Plan

- **Scope**: Refactor the coverage report backend query and calculations. Keep UI in `Filament` but make results correct and fast.
- **Decision**: **Ignore area filters for now** (calculate over users only). We can reintroduce area later.
- **Inputs**: Date range `[from_date, to_date]`, optional filters: `user_id[]`, `grade[]`, `client_type_id[]` (wired in `CoverageReportResource`).
- **References**: `app/Filament/Resources/CoverageReportResource.php`, `app/Models/Reports/CoverageReportData.php` (aggregation entrypoint), `app/Models/Visit.php`, `app/Models/Activity.php`, `app/Models/OfficeWork.php`, `app/Models/OfficialHoliday.php`, `app/Models/VacationRequest.php`, `app/Models/VacationDuration.php`.

### Definitions

- **Weekend days**: Thursday and Friday only. MySQL: `DAYOFWEEK(d) IN (5,6)`.
- **Official holidays**: rows in `official_holidays.date` within range.
- **Vacations**: approved `vacation_requests.approved > 0` with related `vacation_durations (start, end, start_shift, end_shift)`; intersect the range; expand to dates.
- **Activities days**: distinct `DATE(activities.date)` within range.
- **Office work days**: distinct `DATE(office_works.time_from)` within range (assumption: full-day or counted by start date).
- **Working calendar days**: all dates in range excluding weekends and official holidays.
- **Actual working days (per user)**: `count(working_calendar_days) - count(distinct union of: vacation days, activities days, office work days)`; ensure distinct union prevents double subtraction.
- **Total visits (per user)**: count of visits in range (not soft-deleted) where `user_id = rep OR second_user_id = rep`, any status.
- **Actual visits (per user)**: like total visits but `status = 'visited'`.
- **Call rate**: `actual_visits / NULLIF(actual_working_days, 0)`.
- **SOPS**: `actual_visits / NULLIF(daily_target * actual_working_days, 0)`.
- **Daily target**: Source of truth TBD (assume user-level field or default 8). Compute `monthly_visit_target = daily_target * working_calendar_days`.

### Formulas (concise)

- `working_calendar_days = generate_days(from, to) EXCEPT (weekends ∪ official_holidays)`
- `days_off_union(user) = vacation_days(user) ∪ activity_days(user) ∪ office_work_days(user)`
- `actual_working_days(user) = COUNT(working_calendar_days) - COUNT(DISTINCT days_off_union(user))`
- `total_visits(user) = COUNT(visits WHERE in_range AND not_deleted AND (user_id = u OR second_user_id = u))`
- `actual_visits(user) = COUNT(total_visits WHERE status = 'visited')`
- `call_rate(user) = actual_visits / actual_working_days`
- `sops(user) = actual_visits / (daily_target * actual_working_days)`

### SQL helpers to implement (MySQL 8+; use CTEs)

```sql
-- 1) Calendar generator for the date range
WITH RECURSIVE calendar AS (
  SELECT CAST(:from_date AS DATE) AS d
  UNION ALL
  SELECT DATE_ADD(d, INTERVAL 1 DAY)
  FROM calendar
  WHERE d < :to_date
)
SELECT d FROM calendar;
```

```sql
-- 2) Weekend predicate (Thursday=5, Friday=6)
-- MySQL DAYOFWEEK: 1=Sun .. 7=Sat
DAYOFWEEK(d) IN (5,6)
```

```sql
-- 3) Working calendar days (exclude weekends and official holidays)
working_days AS (
  SELECT c.d
  FROM calendar c
  LEFT JOIN official_holidays h ON h.date = c.d
  WHERE NOT (DAYOFWEEK(c.d) IN (5,6)) AND h.id IS NULL
)
```

```sql
-- 4) User days-off unions per user
vacation_days AS (
  -- Expand approved vacation durations into dates using a numbers/calendar join
  SELECT vr.user_id, cal.d
  FROM vacation_requests vr
  JOIN vacation_durations vd ON vd.vacation_request_id = vr.id
  JOIN calendar cal ON cal.d BETWEEN DATE(vd.start) AND DATE(vd.end)
  WHERE vr.approved > 0
),
activity_days AS (
  SELECT a.user_id, DATE(a.date) AS d
  FROM activities a
  WHERE DATE(a.date) BETWEEN :from_date AND :to_date AND a.deleted_at IS NULL
),
office_work_days AS (
  SELECT ow.user_id, DATE(ow.time_from) AS d
  FROM office_works ow
  WHERE DATE(ow.time_from) BETWEEN :from_date AND :to_date
),
days_off AS (
  SELECT user_id, d FROM vacation_days
  UNION
  SELECT user_id, d FROM activity_days
  UNION
  SELECT user_id, d FROM office_work_days
)
```

```sql
-- 5) Visits aggregates
visits_in_range AS (
  SELECT v.*, DATE(v.visit_date) AS d
  FROM visits v
  WHERE DATE(v.visit_date) BETWEEN :from_date AND :to_date
    AND v.deleted_at IS NULL
),
user_visits AS (
  SELECT u.id AS user_id,
         COUNT(*) FILTER (WHERE v.user_id = u.id OR v.second_user_id = u.id) AS total_visits,
         COUNT(*) FILTER (WHERE (v.user_id = u.id OR v.second_user_id = u.id) AND v.status = 'visited') AS actual_visits
  FROM users u
  LEFT JOIN visits_in_range v ON (v.user_id = u.id OR v.second_user_id = u.id)
  GROUP BY u.id
)
```

```sql
-- 6) Actual working days per user
user_actual_working_days AS (
  SELECT u.id AS user_id,
         (SELECT COUNT(*) FROM working_days) -- same for all users for now
         - COALESCE(COUNT(DISTINCT do.d), 0) AS actual_working_days
  FROM users u
  LEFT JOIN days_off do ON do.user_id = u.id AND do.d BETWEEN :from_date AND :to_date
  GROUP BY u.id
)
```

Note: Use standard MySQL syntax; if `FILTER` is unsupported, rewrite with `SUM(CASE WHEN ... THEN 1 ELSE 0 END)`.

### Laravel integration

- **Entry point**: `CoverageReportData::getAggregatedQuery($from, $to, $filters)` should build the above with the query builder (prefer CTEs where supported) or subqueries.
- **Resource**: `CoverageReportResource::getEloquentQuery()` already calls it. Keep filters wired; temporarily ignore `area` in the SQL.
- **Models used**: `Visit`, `Activity`, `OfficeWork`, `OfficialHoliday`, `VacationRequest`, `VacationDuration`.

### TODO (ordered)

- [ ] Confirm weekend definition (Thursday+Friday) and default `daily_target` source (user field vs config; default to 8 if null).
- [ ] Implement calendar CTE and `working_days` subquery in `CoverageReportData::getAggregatedQuery`.
- [ ] Implement vacations expansion using `vacation_durations` intersection with range.
- [ ] Implement `activity_days` and `office_work_days` unions (derive date with `DATE(time_from)`).
- [ ] Compute `actual_working_days` per user as defined (distinct union).
- [ ] Aggregate `total_visits` and `actual_visits` per user (include `second_user_id`).
- [ ] Compute `call_rate`, `sops`, `monthly_visit_target` (guard divide-by-zero).
- [ ] Wire filters: `user_id[]`, `grade[]`, `client_type_id[]`; keep area ignored for now.
- [ ] Validate numbers against a known month manually for 1-2 users.
- [ ] Optimize: add covering indexes (visit_date, user_id, second_user_id), (time_from), (date) on activities, and composite where needed.
- [ ] Add unit tests around date math (weekends, intersections, unions) and query-result smoke test.

### Edge cases

- **Overlaps**: multiple events on the same day counted once (distinct union).
- **Half-day vacations**: `VacationDuration.duration` exists; we still count the date as non-working if any half-day is taken. If needed, add a rule later to count half-days differently.
- **Second rep visits**: both primary and `second_user_id` count for that rep.
- **Soft deletes**: exclude `visits.deleted_at IS NOT NULL`.

### Notes for later

- Reintroduce area-level rollups after core numbers are correct.
- Consider materialized table for per-user-per-day facts if runtime is high.
