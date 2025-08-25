-- User Bricks View
-- This view consolidates all brick access for users through:
-- 1. Direct brick assignments (brick_user table)
-- 2. Area-based brick access (area_user -> area_brick -> bricks)

CREATE OR REPLACE VIEW user_bricks_view AS
SELECT *
FROM (
    -- direct access via brick_user
    SELECT
        u.id   AS user_id,
        u.name AS user_name,
        b.id   AS brick_id,
        b.name AS brick_name,
        a.id   AS area_id,
        a.name AS area_name,
        'direct' AS access_type
    FROM users u
    JOIN brick_user bu ON u.id = bu.user_id
    JOIN bricks b      ON bu.brick_id = b.id
    LEFT JOIN areas a  ON b.area_id = a.id
    WHERE u.is_active = 1

    UNION

    -- indirect access via area_user
    SELECT
        u.id   AS user_id,
        u.name AS user_name,
        b.id   AS brick_id,
        b.name AS brick_name,
        a.id   AS area_id,
        a.name AS area_name,
        'area_based' AS access_type
    FROM users u
    JOIN area_user au   ON u.id = au.user_id
    JOIN areas a        ON au.area_id = a.id
    JOIN bricks b       ON b.area_id = a.id
    WHERE u.is_active = 1
) combined
ORDER BY combined.user_id, combined.brick_id;
