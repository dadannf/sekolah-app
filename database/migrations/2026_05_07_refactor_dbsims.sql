-- Migration: Refactor dbsims schema (2026-05-07)
-- Purpose: Create new normalized tables, copy data from legacy tables,
-- then atomically swap tables and keep backups.
-- IMPORTANT: Run on a staging DB first. Take a full backup before running.

SET FOREIGN_KEY_CHECKS=0;

-- =========================
-- 1) Create new tables (suffix _new)
-- =========================

-- students_new
CREATE TABLE IF NOT EXISTS students_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  nis VARCHAR(32) NOT NULL,
  nisn VARCHAR(32) NULL,
  name VARCHAR(191) NOT NULL,
  gender ENUM('male','female','other') NULL,
  place_of_birth VARCHAR(100) NULL,
  date_of_birth DATE NULL,
  religion VARCHAR(50) NULL,
  phone VARCHAR(30) NULL,
  email VARCHAR(191) NULL,
  photo_path VARCHAR(255) NULL,
  current_grade_level TINYINT UNSIGNED NULL,
  major VARCHAR(100) NULL,
  status ENUM('active','inactive','graduated','dropout') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_students_user_id (user_id),
  UNIQUE KEY uq_students_nis (nis),
  KEY ix_students_nis (nis),
  KEY ix_students_name (name(100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- student_addresses_new
CREATE TABLE IF NOT EXISTS student_addresses_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  address VARCHAR(255) NOT NULL,
  address_rt VARCHAR(10) NULL,
  address_rw VARCHAR(10) NULL,
  address_kelurahan VARCHAR(100) NULL,
  address_kecamatan VARCHAR(100) NULL,
  address_postal_code VARCHAR(20) NULL,
  residence_type VARCHAR(50) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_student_addresses_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- student_parents_new
CREATE TABLE IF NOT EXISTS student_parents_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  father_name VARCHAR(100) NULL,
  father_birth_year SMALLINT UNSIGNED NULL,
  father_nik VARCHAR(32) NULL,
  father_education VARCHAR(100) NULL,
  father_job VARCHAR(100) NULL,
  father_income BIGINT UNSIGNED NULL,
  mother_name VARCHAR(100) NULL,
  mother_birth_year SMALLINT UNSIGNED NULL,
  mother_nik VARCHAR(32) NULL,
  mother_education VARCHAR(100) NULL,
  mother_job VARCHAR(100) NULL,
  mother_income BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_student_parents_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- student_profiles_new
CREATE TABLE IF NOT EXISTS student_profiles_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id BIGINT UNSIGNED NOT NULL,
  hobby VARCHAR(255) NULL,
  aspiration VARCHAR(255) NULL,
  previous_school VARCHAR(255) NULL,
  siblings_count SMALLINT UNSIGNED NULL,
  weight_kg DECIMAL(6,2) NULL,
  height_cm DECIMAL(6,2) NULL,
  waist_cm DECIMAL(6,2) NULL,
  transportation VARCHAR(100) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_student_profiles_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- invoices_new
CREATE TABLE IF NOT EXISTS invoices_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_no VARCHAR(64) NOT NULL,
  student_id BIGINT UNSIGNED NOT NULL,
  invoice_category VARCHAR(64) NOT NULL,
  invoice_year SMALLINT UNSIGNED NOT NULL,
  invoice_month TINYINT UNSIGNED NULL,
  tariff_id BIGINT UNSIGNED NULL,
  amount_due DECIMAL(15,2) NOT NULL,
  due_date DATE NULL,
  status ENUM('unpaid','partial','paid','overdue') NOT NULL DEFAULT 'unpaid',
  notes JSON NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_invoices_invoice_no (invoice_no),
  KEY ix_invoices_student (student_id),
  KEY ix_invoices_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- payments_new
CREATE TABLE IF NOT EXISTS payments_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_id BIGINT UNSIGNED NULL,
  payment_category VARCHAR(64) NULL,
  reference_id BIGINT UNSIGNED NULL,
  amount DECIMAL(15,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL,
  status ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  reference_no VARCHAR(100) NULL,
  bank_name VARCHAR(100) NULL,
  proof_path VARCHAR(255) NULL,
  notes TEXT NULL,
  received_by BIGINT UNSIGNED NULL,
  verified_at DATETIME NULL,
  verified_by BIGINT UNSIGNED NULL,
  ocr_receipt_id BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_payments_status (status),
  KEY ix_payments_paid_at (paid_at),
  KEY ix_payments_invoice (invoice_id),
  KEY ix_payments_ocr_receipt (ocr_receipt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ocr_payment_receipts_new
CREATE TABLE IF NOT EXISTS ocr_payment_receipts_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  payment_id BIGINT UNSIGNED NULL,
  ocr_engine VARCHAR(64) NULL,
  processing_time_ms INT UNSIGNED NULL,
  raw_json LONGTEXT NULL,
  status ENUM('pending','processed','failed') NOT NULL DEFAULT 'pending',
  recognized_amount DECIMAL(15,2) NULL,
  recognized_invoice_no VARCHAR(128) NULL,
  image_path VARCHAR(255) NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_ocr_receipts_status (status),
  KEY ix_ocr_receipts_payment (payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- notifications_new
CREATE TABLE IF NOT EXISTS notifications_new (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NULL,
  performed_by BIGINT UNSIGNED NULL,
  type VARCHAR(100) NOT NULL,
  message TEXT NOT NULL,
  data JSON NULL,
  read_at DATETIME NULL,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ix_notifications_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
-- 2) Copy data from legacy tables into new tables
-- Note: adapt mappings if your legacy field names differ
-- =========================

-- Copy students core fields
INSERT INTO students_new (id, user_id, nis, nisn, name, place_of_birth, date_of_birth, gender, current_grade_level, major, email, phone, photo_path, status, created_at, updated_at)
SELECT id, user_id, nis, nisn, name, place_of_birth, date_of_birth, gender, current_grade_level, major, email, phone_number, photo_path,
       COALESCE(NULLIF(student_status, ''), 'active') as status, created_at, updated_at
FROM students;

-- Extract parents info from old students table (if present)
INSERT INTO student_parents_new (student_id, father_name, mother_name, created_at, updated_at)
SELECT id, father_name, mother_name, created_at, updated_at FROM students;

-- Addresses: attempt to move 'address' field from students
INSERT INTO student_addresses_new (student_id, address, created_at, updated_at)
SELECT id, address, created_at, updated_at FROM students WHERE address IS NOT NULL AND address <> '';

-- Profiles: minimal extraction
INSERT INTO student_profiles_new (student_id, created_at, updated_at)
SELECT id, created_at, updated_at FROM students;

-- Copy invoices from spp_invoices (if exists)
INSERT INTO invoices_new (id, invoice_no, student_id, invoice_category, invoice_year, invoice_month, tariff_id, amount_due, due_date, status, notes, created_at, updated_at)
SELECT id, CONCAT('SPP-', id), student_id, 'spp', invoice_year, invoice_month, tariff_id, amount_due, due_date,
       CASE WHEN status='void' THEN 'unpaid' ELSE status END, NULL, created_at, updated_at
FROM spp_invoices;

-- Copy payments from payments (legacy)
INSERT INTO payments_new (id, invoice_id, payment_category, reference_id, amount, payment_method, status, paid_at, reference_no, bank_name, proof_path, notes, received_by, verified_at, verified_by, created_at, updated_at)
SELECT id, invoice_id, NULL, NULL, amount, method, 
       CASE WHEN status='submitted' THEN 'pending' ELSE status END,
       paid_at, reference_no, bank_name, proof_path, notes, received_by, verified_at, verified_by, created_at, updated_at
FROM payments;

-- Copy OCR receipts (if any legacy table exists; adapt if OCR was stored differently)
-- If legacy OCR table not present, skip this step
-- Example: if old table name is ocr_payment_receipts use it; otherwise populate later via application
INSERT INTO ocr_payment_receipts_new (id, payment_id, raw_json, status, created_at, updated_at)
SELECT id, payment_id, raw_json, status, created_at, updated_at FROM ocr_payment_receipts
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='ocr_payment_receipts');

-- Copy notifications (if legacy table named notifications exists)
INSERT INTO notifications_new (id, user_id, performed_by, type, message, data, read_at, created_at, updated_at)
SELECT id, user_id, performed_by, type, message, data, read_at, created_at, updated_at FROM notifications
WHERE EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='notifications');

-- =========================
-- 3) Add foreign keys on *_new tables
-- =========================

-- Add FKs for students_new
ALTER TABLE students_new
  ADD CONSTRAINT fk_students_new_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE student_addresses_new
  ADD CONSTRAINT fk_student_addresses_new_student FOREIGN KEY (student_id) REFERENCES students_new(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE student_parents_new
  ADD CONSTRAINT fk_student_parents_new_student FOREIGN KEY (student_id) REFERENCES students_new(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE student_profiles_new
  ADD CONSTRAINT fk_student_profiles_new_student FOREIGN KEY (student_id) REFERENCES students_new(id) ON UPDATE CASCADE ON DELETE CASCADE;

ALTER TABLE invoices_new
  ADD CONSTRAINT fk_invoices_new_student FOREIGN KEY (student_id) REFERENCES students_new(id) ON UPDATE CASCADE ON DELETE CASCADE;

-- Tariff FK: set null on delete (keeps invoice record)
ALTER TABLE invoices_new
  ADD CONSTRAINT fk_invoices_new_tariff FOREIGN KEY (tariff_id) REFERENCES spp_tariffs(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE payments_new
  ADD CONSTRAINT fk_payments_new_invoice FOREIGN KEY (invoice_id) REFERENCES invoices_new(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE payments_new
  ADD CONSTRAINT fk_payments_new_received_by FOREIGN KEY (received_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE payments_new
  ADD CONSTRAINT fk_payments_new_verified_by FOREIGN KEY (verified_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE ocr_payment_receipts_new
  ADD CONSTRAINT fk_ocr_new_payment FOREIGN KEY (payment_id) REFERENCES payments_new(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE notifications_new
  ADD CONSTRAINT fk_notifications_new_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE;

-- =========================
-- 4) Swap tables atomically by renaming (keep backups with _old suffix)
-- =========================

RENAME TABLE
  students TO students_old,
  students_new TO students,
  student_addresses TO student_addresses_old,
  student_addresses_new TO student_addresses,
  student_parents TO student_parents_old,
  student_parents_new TO student_parents,
  student_profiles TO student_profiles_old,
  student_profiles_new TO student_profiles,
  spp_invoices TO spp_invoices_old,
  invoices_new TO invoices,
  payments TO payments_old,
  payments_new TO payments,
  ocr_payment_receipts TO ocr_payment_receipts_old,
  ocr_payment_receipts_new TO ocr_payment_receipts,
  notifications TO notifications_old,
  notifications_new TO notifications;

-- =========================
-- 5) Re-enable foreign keys and finish
-- =========================
SET FOREIGN_KEY_CHECKS=1;

-- Note: After running, verify counts and spot check sample rows.
-- If satisfied, you may DROP TABLE <table>_old to reclaim space.

-- Example verification queries:
-- SELECT COUNT(*) FROM students_old;
-- SELECT COUNT(*) FROM students;
-- SELECT s.id, s.name, sa.address FROM students s LEFT JOIN student_addresses sa ON s.id=sa.student_id LIMIT 20;

-- End of migration
