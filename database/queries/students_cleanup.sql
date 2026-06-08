-- Students table cleanup plan
-- Purpose: standardize contact data and provide a staged cleanup path.
-- Run on staging first and back up the database before applying DROP COLUMN statements.

START TRANSACTION;

-- 1) Normalize main phone field from legacy columns.
UPDATE students
SET phone_number = COALESCE(NULLIF(phone_number, ''), NULLIF(mobile_phone, ''), NULLIF(telephone, ''))
WHERE phone_number IS NULL OR phone_number = '';

-- 2) Optional: capture a snapshot of columns that will be removed later.
-- This script intentionally keeps the DROP COLUMN block commented out so it can be reviewed safely.

-- 3) Final cleanup block for when the app has been updated and validated.
-- ALTER TABLE students
--   DROP COLUMN telephone,
--   DROP COLUMN mobile_phone,
--   DROP COLUMN weight_kg,
--   DROP COLUMN height_cm,
--   DROP COLUMN waist_cm,
--   DROP COLUMN distance_to_school,
--   DROP COLUMN travel_time,
--   DROP COLUMN siblings_count,
--   DROP COLUMN previous_school,
--   DROP COLUMN hobby,
--   DROP COLUMN aspiration,
--   DROP COLUMN birth_certificate_registration_no,
--   DROP COLUMN shirt_size,
--   DROP COLUMN diploma_serial_no,
--   DROP COLUMN previous_school_npsn,
--   DROP COLUMN transportation,
--   DROP COLUMN residence_type,
--   DROP COLUMN father_income,
--   DROP COLUMN mother_income;

COMMIT;