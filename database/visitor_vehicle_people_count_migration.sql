-- GOLDEN HOMES per-vehicle passenger count
-- BISM4RCK/KUN3H0 2026
ALTER TABLE visitor_request_vehicles
    ADD COLUMN IF NOT EXISTS people_count INT UNSIGNED NOT NULL DEFAULT 1;
