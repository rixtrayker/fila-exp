CREATE PROCEDURE GetSOPsAndCallRateData(
    IN p_from_date DATE,
    IN p_to_date DATE,
    IN p_user_ids TEXT,
    IN p_client_type_id INT
)
READS SQL DATA
BEGIN
    DECLARE v_from_date DATE;
    DECLARE v_to_date DATE;
    DECLARE v_total_working_days INT DEFAULT 0;
    DECLARE v_daily_target INT DEFAULT 6;

    -- Set default date range (current month if not provided)
    SET v_from_date = IFNULL(p_from_date, DATE_FORMAT(CURDATE(), '%Y-%m-01'));
    SET v_to_date = IFNULL(p_to_date, CURDATE());

    -- Calculate total working days once for the entire period (excluding weekends and holidays)
    SELECT COUNT(DISTINCT cal_date) INTO v_total_working_days
    FROM (
        SELECT v_from_date + INTERVAL (t4.i*1000 + t3.i*100 + t2.i*10 + t1.i) DAY as cal_date
        FROM
            (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
            (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
            (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
            (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4
    ) dates
    WHERE cal_date >= v_from_date
      AND cal_date <= v_to_date
      AND DAYOFWEEK(cal_date) NOT IN (1, 7) -- Exclude weekends
      AND cal_date NOT IN (
          SELECT DATE(oh.date)
          FROM official_holidays oh
          WHERE DATE(oh.date) BETWEEN v_from_date AND v_to_date
      );

    -- Get daily target based on client type with fallback to settings
    CASE p_client_type_id
        WHEN 1 THEN -- AM
            SELECT COALESCE(CAST(value AS UNSIGNED), 2) INTO v_daily_target
            FROM settings WHERE `key` = 'daily_am_target' LIMIT 1;
        WHEN 3 THEN -- PH
            SELECT COALESCE(CAST(value AS UNSIGNED), 8) INTO v_daily_target
            FROM settings WHERE `key` = 'daily_ph_target' LIMIT 1;
        ELSE -- PM (default)
            SELECT COALESCE(CAST(value AS UNSIGNED), 6) INTO v_daily_target
            FROM settings WHERE `key` = 'daily_pm_target' LIMIT 1;
    END CASE;

    -- Main query with reusable subqueries
    SELECT
        u.id,
        u.name,
        COALESCE(GROUP_CONCAT(DISTINCT a.name SEPARATOR ', '), 'No Area') as area_name,

        -- Pre-calculated values (same for all users)
        v_total_working_days as working_days,
        v_daily_target as daily_visit_target,

        -- Simple counts (filtered by date range)
        COALESCE(office_work_count, 0) as office_work_count,
        COALESCE(activities_count, 0) as activities_count,

        -- Complex calculations using subqueries
        COALESCE(busy_days_count, 0) as busy_days_count,
        COALESCE(actual_working_days_calc.actual_working_days, 0) as actual_working_days,
        (COALESCE(actual_working_days_calc.actual_working_days, 0) * v_daily_target) as monthly_visit_target,

        -- Visit metrics
        COALESCE(actual_visits, 0) as actual_visits,
        COALESCE(total_visits, 0) as total_visits,
        COALESCE(vacation_days, 0) as vacation_days,

        -- Performance metrics
        CASE
            WHEN COALESCE(actual_working_days_calc.actual_working_days, 0) > 0
            THEN ROUND(COALESCE(actual_visits, 0) / COALESCE(actual_working_days_calc.actual_working_days, 0), 2)
            ELSE 0
        END as call_rate,

        CASE
            WHEN (COALESCE(actual_working_days_calc.actual_working_days, 0) * v_daily_target) > 0
            THEN ROUND((COALESCE(actual_visits, 0) / (COALESCE(actual_working_days_calc.actual_working_days, 0) * v_daily_target)) * 100, 2)
            ELSE 0
        END as sops,

        -- Daily report number (distinct working dates)
        COALESCE(daily_report_no, 0) as daily_report_no

    FROM users u
    LEFT JOIN area_user au ON u.id = au.user_id
    LEFT JOIN areas a ON au.area_id = a.id

    -- Office work count subquery (distinct dates)
    LEFT JOIN (
        SELECT
            ow.user_id,
            COUNT(DISTINCT DATE(ow.created_at)) as office_work_count
        FROM office_works ow
        WHERE DATE(ow.created_at) BETWEEN v_from_date AND v_to_date
          AND ow.status = 'approved'
        GROUP BY ow.user_id
    ) ow_counts ON u.id = ow_counts.user_id

    -- Activities count subquery (distinct dates)
    LEFT JOIN (
        SELECT
            act.user_id,
            COUNT(DISTINCT DATE(act.date)) as activities_count
        FROM activities act
        WHERE DATE(act.date) BETWEEN v_from_date AND v_to_date
        GROUP BY act.user_id
    ) act_counts ON u.id = act_counts.user_id

    -- Busy days calculation (office work + activities + vacation) - CALCULATED ONCE
    LEFT JOIN (
        SELECT
            user_id,
            COUNT(DISTINCT busy_date) as busy_days_count
        FROM (
            -- Days with office work
            SELECT
                ow.user_id,
                DATE(ow.created_at) as busy_date
            FROM office_works ow
            WHERE DATE(ow.created_at) BETWEEN v_from_date AND v_to_date
              AND ow.status = 'approved'
            UNION

            -- Days with activities
            SELECT
                act.user_id,
                DATE(act.date) as busy_date
            FROM activities act
            WHERE DATE(act.date) BETWEEN v_from_date AND v_to_date

            UNION

            -- Vacation days
            SELECT
                vr.user_id,
                DATE(vd.start + INTERVAL offset_days.offset DAY) as busy_date
            FROM vacation_durations vd
            JOIN vacation_requests vr ON vd.vacation_request_id = vr.id
            CROSS JOIN (
                SELECT 0 as offset UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13
                UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27
                UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
            ) offset_days
            WHERE vr.approved = 1
              AND DATE(vd.start + INTERVAL offset_days.offset DAY) <= vd.end
              AND DATE(vd.start + INTERVAL offset_days.offset DAY) BETWEEN v_from_date AND v_to_date
        ) all_busy_days
        GROUP BY user_id
    ) busy_days ON u.id = busy_days.user_id

    -- Actual working days calculation (working days minus distinct busy dates, excluding weekends and holidays)
    LEFT JOIN (
        SELECT
            user_id,
            COUNT(DISTINCT work_date) as actual_working_days
        FROM (
            SELECT
                u.id as user_id,
                cal_date as work_date
            FROM users u
            CROSS JOIN (
                SELECT v_from_date + INTERVAL (t4.i*1000 + t3.i*100 + t2.i*10 + t1.i) DAY as cal_date
                FROM
                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t1,
                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t2,
                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t3,
                    (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) t4
            ) dates
            WHERE cal_date >= v_from_date
              AND cal_date <= v_to_date
              AND DAYOFWEEK(cal_date) NOT IN (1, 7) -- Exclude weekends
              AND cal_date NOT IN (
                  SELECT DATE(oh.date)
                  FROM official_holidays oh
                  WHERE DATE(oh.date) BETWEEN v_from_date AND v_to_date
              )
              AND cal_date NOT IN (
                  -- Exclude office work dates
                  SELECT DISTINCT DATE(ow.created_at)
                  FROM office_works ow
                  WHERE ow.user_id = u.id
                    AND DATE(ow.created_at) BETWEEN v_from_date AND v_to_date
                    AND ow.status = 'approved'
              )
              AND cal_date NOT IN (
                  -- Exclude activity dates
                  SELECT DISTINCT DATE(act.date)
                  FROM activities act
                  WHERE act.user_id = u.id
                    AND DATE(act.date) BETWEEN v_from_date AND v_to_date
              )
              AND cal_date NOT IN (
                  -- Exclude vacation dates
                  SELECT DISTINCT DATE(vd.start + INTERVAL offset_days.offset DAY)
                  FROM vacation_durations vd
                  JOIN vacation_requests vr ON vd.vacation_request_id = vr.id
                  CROSS JOIN (
                      SELECT 0 as offset UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6
                      UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13
                      UNION SELECT 14 UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                      UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25 UNION SELECT 26 UNION SELECT 27
                      UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
                  ) offset_days
                  WHERE vr.user_id = u.id
                    AND vr.approved = 1
                    AND DATE(vd.start + INTERVAL offset_days.offset DAY) <= vd.end
                    AND DATE(vd.start + INTERVAL offset_days.offset DAY) BETWEEN v_from_date AND v_to_date
              )
        ) available_work_days
        GROUP BY user_id
    ) actual_working_days_calc ON u.id = actual_working_days_calc.user_id

    -- Actual visits subquery
    LEFT JOIN (
        SELECT
            CASE WHEN v.second_user_id IS NOT NULL THEN v.second_user_id ELSE v.user_id END as user_id,
            COUNT(*) as actual_visits
        FROM visits v
        LEFT JOIN clients c ON v.client_id = c.id
        WHERE v.status = 'visited'
          AND DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
          AND v.deleted_at IS NULL
          AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)
        GROUP BY CASE WHEN v.second_user_id IS NOT NULL THEN v.second_user_id ELSE v.user_id END

        UNION ALL

        SELECT
            v.user_id,
            COUNT(*) as actual_visits
        FROM visits v
        LEFT JOIN clients c ON v.client_id = c.id
        WHERE v.status = 'visited'
          AND DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
          AND v.deleted_at IS NULL
          AND v.second_user_id IS NOT NULL
          AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)
        GROUP BY v.user_id
    ) actual_visits_counts ON u.id = actual_visits_counts.user_id

    -- Total visits subquery
    LEFT JOIN (
        SELECT
            CASE WHEN v.second_user_id IS NOT NULL THEN v.second_user_id ELSE v.user_id END as user_id,
            COUNT(*) as total_visits
        FROM visits v
        LEFT JOIN clients c ON v.client_id = c.id
        WHERE DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
          AND v.deleted_at IS NULL
          AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)
        GROUP BY CASE WHEN v.second_user_id IS NOT NULL THEN v.second_user_id ELSE v.user_id END

        UNION ALL

        SELECT
            v.user_id,
            COUNT(*) as total_visits
        FROM visits v
        LEFT JOIN clients c ON v.client_id = c.id
        WHERE DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
          AND v.deleted_at IS NULL
          AND v.second_user_id IS NOT NULL
          AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)
        GROUP BY v.user_id
    ) total_visits_counts ON u.id = total_visits_counts.user_id

    -- Vacation days subquery (counting whole days as integers)
    LEFT JOIN (
        SELECT
            vr.user_id,
            CAST(SUM(
                CASE
                    WHEN vd.start < v_from_date THEN
                        CASE WHEN vd.end > v_to_date THEN DATEDIFF(v_to_date, v_from_date) + 1
                             ELSE DATEDIFF(vd.end, v_from_date) + 1 END
                    WHEN vd.end > v_to_date THEN DATEDIFF(v_to_date, vd.start) + 1
                    ELSE DATEDIFF(vd.end, vd.start) + 1
                END
            ) AS UNSIGNED) as vacation_days
        FROM vacation_durations vd
        JOIN vacation_requests vr ON vd.vacation_request_id = vr.id
        WHERE vr.approved = 1
          AND vd.start <= v_to_date
          AND vd.end >= v_from_date
        GROUP BY vr.user_id
    ) vacation_counts ON u.id = vacation_counts.user_id

    -- Daily report number subquery (distinct working dates from visits, office work, and activities)
    LEFT JOIN (
        SELECT
            user_id,
            COUNT(DISTINCT work_date) as daily_report_no
        FROM (
            -- Distinct dates from visited visits
            SELECT
                CASE WHEN v.second_user_id IS NOT NULL THEN v.second_user_id ELSE v.user_id END as user_id,
                DATE(v.visit_date) as work_date
            FROM visits v
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE v.status = 'visited'
              AND DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
              AND v.deleted_at IS NULL
              AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)

            UNION

            SELECT
                v.user_id,
                DATE(v.visit_date) as work_date
            FROM visits v
            LEFT JOIN clients c ON v.client_id = c.id
            WHERE v.status = 'visited'
              AND DATE(v.visit_date) BETWEEN v_from_date AND v_to_date
              AND v.deleted_at IS NULL
              AND v.second_user_id IS NOT NULL
              AND (p_client_type_id = 0 OR c.client_type_id = p_client_type_id)

            UNION

            -- Distinct dates from office work
            SELECT
                ow.user_id,
                DATE(ow.created_at) as work_date
            FROM office_works ow
            WHERE DATE(ow.created_at) BETWEEN v_from_date AND v_to_date
              AND ow.status = 'approved'
            UNION

            -- Distinct dates from activities
            SELECT
                act.user_id,
                DATE(act.date) as work_date
            FROM activities act
            WHERE DATE(act.date) BETWEEN v_from_date AND v_to_date
              AND act.deleted_at IS NULL
        ) all_work_days
        GROUP BY user_id
    ) daily_report_counts ON u.id = daily_report_counts.user_id

    WHERE (p_user_ids = '' OR FIND_IN_SET(u.id, p_user_ids))
    GROUP BY u.id, u.name,
             office_work_count, activities_count, busy_days_count,
             actual_working_days_calc.actual_working_days,
             actual_visits, total_visits, vacation_days, daily_report_no
    ORDER BY u.name ASC;

END;
