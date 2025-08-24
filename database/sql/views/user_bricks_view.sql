-- User Bricks View
-- This view consolidates all brick access for users through:
-- 1. Direct brick assignments (brick_user table)
-- 2. Area-based brick access (area_user -> area_brick -> bricks)

CREATE OR REPLACE VIEW user_bricks_view AS
SELECT DISTINCT
    u.id as user_id,
    u.name as user_name,
    b.id as brick_id,
    b.name as brick_name,
    a.id as area_id,
    a.name as area_name,
    'direct' as access_type
FROM users u
-- Direct brick assignments
JOIN brick_user bu ON u.id = bu.user_id
JOIN bricks b ON bu.brick_id = b.id
LEFT JOIN areas a ON b.area_id = a.id
WHERE u.is_active = 1

UNION

-- Area-based brick access
SELECT DISTINCT
    u.id as user_id,
    u.name as user_name,
    b.id as brick_id,
    b.name as brick_name,
    a.id as area_id,
    a.name as area_name,
    'area_based' as access_type
FROM users u
JOIN area_user au ON u.id = au.user_id
JOIN areas a ON au.area_id = a.id
JOIN area_brick ab ON a.id = ab.area_id
JOIN bricks b ON ab.brick_id = b.id
WHERE u.is_active = 1

ORDER BY user_id, brick_id;
