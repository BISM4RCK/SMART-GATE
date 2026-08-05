-- GOLDEN HOMES visitor no-ID option
-- BISM4RCK/KUN3H0 2026
-- Keeps the visitor's choice explicit without requiring an ID file.
ALTER TABLE visitors ADD COLUMN IF NOT EXISTS no_id TINYINT(1) NOT NULL DEFAULT 0;
