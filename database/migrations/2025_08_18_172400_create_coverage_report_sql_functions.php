<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Function to generate working calendar days (excluding weekends and holidays)
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_working_calendar_days;
            CREATE FUNCTION get_working_calendar_days(start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE working_days INT DEFAULT 0;

                WITH RECURSIVE calendar AS (
                    SELECT start_date AS d
                    UNION ALL
                    SELECT DATE_ADD(d, INTERVAL 1 DAY)
                    FROM calendar
                    WHERE d < end_date
                )
                SELECT COUNT(*) INTO working_days
                FROM calendar c
                LEFT JOIN official_holidays h ON h.date = c.d
                WHERE NOT (DAYOFWEEK(c.d) IN (5,6)) AND h.id IS NULL;

                RETURN working_days;
            END
        ');

        // 2. Function to get user days off count (vacations + activities + office work)
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_days_off_count;
            CREATE FUNCTION get_user_days_off_count(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE days_off_count INT DEFAULT 0;

                WITH RECURSIVE calendar AS (
                    SELECT start_date AS d
                    UNION ALL
                    SELECT DATE_ADD(d, INTERVAL 1 DAY)
                    FROM calendar
                    WHERE d < end_date
                )
                SELECT COUNT(DISTINCT d) INTO days_off_count
                FROM (
                    -- Vacation days
                    SELECT cal.d
                    FROM vacation_requests vr
                    JOIN vacation_durations vd ON vd.vacation_request_id = vr.id
                    JOIN calendar cal ON cal.d BETWEEN DATE(vd.start) AND DATE(vd.end)
                    WHERE vr.user_id = user_id AND vr.approved > 0

                    UNION

                    -- Activity days
                    SELECT DATE(a.date) AS d
                    FROM activities a
                    WHERE a.user_id = user_id
                      AND DATE(a.date) BETWEEN start_date AND end_date
                      AND a.deleted_at IS NULL

                    UNION

                    -- Office work days
                    SELECT DATE(ow.time_from) AS d
                    FROM office_works ow
                    WHERE ow.user_id = user_id
                      AND DATE(ow.time_from) BETWEEN start_date AND end_date
                ) days_off_union;

                RETURN COALESCE(days_off_count, 0);
            END
        ');

        // 3. Function to get user total visits (including second_user_id)
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_total_visits;
            CREATE FUNCTION get_user_total_visits(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE total_visits INT DEFAULT 0;

                SELECT COUNT(*) INTO total_visits
                FROM visits v
                WHERE (v.user_id = user_id OR v.second_user_id = user_id)
                  AND DATE(v.visit_date) BETWEEN start_date AND end_date
                  AND v.deleted_at IS NULL;

                RETURN COALESCE(total_visits, 0);
            END
        ');

        // 4. Function to get user actual visits (status = visited, including second_user_id)
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_actual_visits;
            CREATE FUNCTION get_user_actual_visits(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE actual_visits INT DEFAULT 0;

                SELECT COUNT(*) INTO actual_visits
                FROM visits v
                WHERE (v.user_id = user_id OR v.second_user_id = user_id)
                  AND v.status = "visited"
                  AND DATE(v.visit_date) BETWEEN start_date AND end_date
                  AND v.deleted_at IS NULL;

                RETURN COALESCE(actual_visits, 0);
            END
        ');

        // 5. Function to get user actual visits by client type
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_actual_visits_by_client_type;
            CREATE FUNCTION get_user_actual_visits_by_client_type(user_id INT, start_date DATE, end_date DATE, client_type_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE actual_visits INT DEFAULT 0;

                SELECT COUNT(*) INTO actual_visits
                FROM visits v
                JOIN clients c ON v.client_id = c.id
                WHERE (v.user_id = user_id OR v.second_user_id = user_id)
                  AND v.status = "visited"
                  AND DATE(v.visit_date) BETWEEN start_date AND end_date
                  AND v.deleted_at IS NULL
                  AND c.client_type_id = client_type_id;

                RETURN COALESCE(actual_visits, 0);
            END
        ');

        // 6. Function to calculate user actual working days
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_actual_working_days;
            CREATE FUNCTION get_user_actual_working_days(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE working_calendar_days INT;
                DECLARE days_off INT;
                DECLARE actual_working_days INT;

                SET working_calendar_days = get_working_calendar_days(start_date, end_date);
                SET days_off = get_user_days_off_count(user_id, start_date, end_date);
                SET actual_working_days = GREATEST(0, working_calendar_days - days_off);

                RETURN actual_working_days;
            END
        ');

        // 7. Function to calculate call rate
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_call_rate;
            CREATE FUNCTION get_user_call_rate(user_id INT, start_date DATE, end_date DATE)
            RETURNS DECIMAL(10,2)
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE actual_visits INT;
                DECLARE actual_working_days INT;
                DECLARE call_rate DECIMAL(10,2);

                SET actual_visits = get_user_actual_visits(user_id, start_date, end_date);
                SET actual_working_days = get_user_actual_working_days(user_id, start_date, end_date);

                IF actual_working_days > 0 THEN
                    SET call_rate = ROUND(actual_visits / actual_working_days, 2);
                ELSE
                    SET call_rate = 0.00;
                END IF;

                RETURN call_rate;
            END
        ');

        // 8. Function to calculate SOPS
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_sops;
            CREATE FUNCTION get_user_sops(user_id INT, start_date DATE, end_date DATE, daily_target INT)
            RETURNS DECIMAL(10,2)
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE actual_visits INT;
                DECLARE actual_working_days INT;
                DECLARE sops DECIMAL(10,2);

                SET actual_visits = get_user_actual_visits(user_id, start_date, end_date);
                SET actual_working_days = get_user_actual_working_days(user_id, start_date, end_date);

                IF actual_working_days > 0 AND daily_target > 0 THEN
                    SET sops = ROUND((actual_visits / (daily_target * actual_working_days)) * 100, 2);
                ELSE
                    SET sops = 0.00;
                END IF;

                RETURN sops;
            END
        ');

        // 9. Function to get user total visits by client type
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_total_visits_by_client_type;
            CREATE FUNCTION get_user_total_visits_by_client_type(user_id INT, start_date DATE, end_date DATE, client_type_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE total_visits INT DEFAULT 0;

                SELECT COUNT(*) INTO total_visits
                FROM visits v
                JOIN clients c ON v.client_id = c.id
                WHERE (v.user_id = user_id OR v.second_user_id = user_id)
                  AND DATE(v.visit_date) BETWEEN start_date AND end_date
                  AND v.deleted_at IS NULL
                  AND c.client_type_id = client_type_id;

                RETURN COALESCE(total_visits, 0);
            END
        ');

        // 10. Function to get user office work count
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_office_work_count;
            CREATE FUNCTION get_user_office_work_count(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE office_work_count INT DEFAULT 0;

                SELECT COUNT(*) INTO office_work_count
                FROM office_works ow
                WHERE ow.user_id = user_id
                  AND DATE(ow.time_from) BETWEEN start_date AND end_date;

                RETURN COALESCE(office_work_count, 0);
            END
        ');

        // 11. Function to get user activities count
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_activities_count;
            CREATE FUNCTION get_user_activities_count(user_id INT, start_date DATE, end_date DATE)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE activities_count INT DEFAULT 0;

                SELECT COUNT(*) INTO activities_count
                FROM activities act
                WHERE act.user_id = user_id
                  AND DATE(act.date) BETWEEN start_date AND end_date
                  AND act.deleted_at IS NULL;

                RETURN COALESCE(activities_count, 0);
            END
        ');

        // 12. Function to get user daily target from settings
        DB::unprepared('
            DROP FUNCTION IF EXISTS get_user_daily_target;
            CREATE FUNCTION get_user_daily_target(client_type_id INT)
            RETURNS INT
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE setting_key VARCHAR(50);
                DECLARE daily_target INT;

                SET setting_key = CASE
                    WHEN client_type_id = 1 THEN "daily_am_target"
                    WHEN client_type_id = 3 THEN "daily_ph_target"
                    ELSE "daily_pm_target"
                END;

                SELECT COALESCE(CAST(value AS UNSIGNED),
                    CASE
                        WHEN client_type_id = 1 THEN 2
                        WHEN client_type_id = 3 THEN 8
                        ELSE 6
                    END
                ) INTO daily_target
                FROM settings
                WHERE `key` = setting_key
                LIMIT 1;

                RETURN daily_target;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS get_working_calendar_days');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_days_off_count');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_total_visits');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_actual_visits');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_actual_visits_by_client_type');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_actual_working_days');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_call_rate');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_sops');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_total_visits_by_client_type');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_office_work_count');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_activities_count');
        DB::unprepared('DROP FUNCTION IF EXISTS get_user_daily_target');
    }
};
