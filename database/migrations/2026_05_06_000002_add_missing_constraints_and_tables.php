<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create missing core tables used by models.
        if (!Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('year_label', 20)->unique();
                $table->boolean('is_active')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->string('grade_code', 20)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
                $table->foreignId('grade_id')->constrained('grades')->restrictOnDelete();
                $table->string('status', 30)->nullable();
                $table->timestamps();

                $table->index(['student_id', 'academic_year_id']);
                $table->index(['academic_year_id', 'grade_id']);
            });
        }

        // Fix missing foreign keys on spp_invoices.
        if (Schema::hasTable('spp_invoices')) {
            try {
                DB::statement(
                    'ALTER TABLE `spp_invoices` '
                    . 'ADD CONSTRAINT `fk_invoice_student` '
                    . 'FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) '
                    . 'ON DELETE CASCADE ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `spp_invoices` '
                    . 'ADD CONSTRAINT `fk_invoice_tariff_ref` '
                    . 'FOREIGN KEY (`tariff_id`) REFERENCES `spp_tariffs` (`id`) '
                    . 'ON DELETE RESTRICT ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `spp_invoices` '
                    . 'ADD INDEX `ix_invoice_student_year_type` (`student_id`, `invoice_year`, `invoice_type`)'
                );
            } catch (Throwable $e) {
                // Ignore if index already exists.
            }
        }

        // Add foreign keys for information.
        if (Schema::hasTable('information')) {
            try {
                DB::statement(
                    'ALTER TABLE `information` '
                    . 'ADD CONSTRAINT `fk_information_created_by_user` '
                    . 'FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) '
                    . 'ON DELETE RESTRICT ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `information` '
                    . 'ADD CONSTRAINT `fk_information_target_student_ref` '
                    . 'FOREIGN KEY (`target_student_id`) REFERENCES `students` (`id`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }
        }

        // Add foreign keys for OCR receipts.
        if (Schema::hasTable('ocr_payment_receipts')) {
            try {
                DB::statement(
                    'ALTER TABLE `ocr_payment_receipts` '
                    . 'ADD CONSTRAINT `fk_ocr_payment_ref` '
                    . 'FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `ocr_payment_receipts` '
                    . 'ADD CONSTRAINT `fk_ocr_student_ref` '
                    . 'FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `ocr_payment_receipts` '
                    . 'ADD CONSTRAINT `fk_ocr_uploaded_by_user` '
                    . 'FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement(
                    'ALTER TABLE `ocr_payment_receipts` '
                    . 'ADD CONSTRAINT `fk_ocr_verified_by_user` '
                    . 'FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) '
                    . 'ON DELETE SET NULL ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }
        }

        // Add missing index/FK for student_payments if table exists.
        if (Schema::hasTable('student_payments')) {
            try {
                DB::statement(
                    'ALTER TABLE `student_payments` '
                    . 'ADD CONSTRAINT `student_payments_academic_year_id_foreign` '
                    . 'FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) '
                    . 'ON DELETE CASCADE ON UPDATE CASCADE'
                );
            } catch (Throwable $e) {
                // Ignore if FK already exists.
            }

            try {
                DB::statement('ALTER TABLE `student_payments` ADD INDEX `student_payments_academic_year_id_index` (`academic_year_id`)');
            } catch (Throwable $e) {
                // Ignore if index already exists.
            }

            try {
                DB::statement('ALTER TABLE `student_payments` ADD INDEX `student_payments_status_index` (`status`)');
            } catch (Throwable $e) {
                // Ignore if index already exists.
            }
        }
    }

    public function down(): void
    {
        // Drop FKs and indexes added to student_payments.
        if (Schema::hasTable('student_payments')) {
            try {
                DB::statement('ALTER TABLE `student_payments` DROP FOREIGN KEY `student_payments_academic_year_id_foreign`');
            } catch (Throwable $e) {
                // Ignore if FK does not exist.
            }

            try {
                DB::statement('ALTER TABLE `student_payments` DROP INDEX `student_payments_academic_year_id_index`');
            } catch (Throwable $e) {
                // Ignore if index does not exist.
            }

            try {
                DB::statement('ALTER TABLE `student_payments` DROP INDEX `student_payments_status_index`');
            } catch (Throwable $e) {
                // Ignore if index does not exist.
            }
        }

        // Drop OCR FKs.
        if (Schema::hasTable('ocr_payment_receipts')) {
            try {
                DB::statement('ALTER TABLE `ocr_payment_receipts` DROP FOREIGN KEY `fk_ocr_payment_ref`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `ocr_payment_receipts` DROP FOREIGN KEY `fk_ocr_student_ref`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `ocr_payment_receipts` DROP FOREIGN KEY `fk_ocr_uploaded_by_user`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `ocr_payment_receipts` DROP FOREIGN KEY `fk_ocr_verified_by_user`');
            } catch (Throwable $e) {
            }
        }

        // Drop information FKs.
        if (Schema::hasTable('information')) {
            try {
                DB::statement('ALTER TABLE `information` DROP FOREIGN KEY `fk_information_created_by_user`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `information` DROP FOREIGN KEY `fk_information_target_student_ref`');
            } catch (Throwable $e) {
            }
        }

        // Drop spp_invoices FKs and index.
        if (Schema::hasTable('spp_invoices')) {
            try {
                DB::statement('ALTER TABLE `spp_invoices` DROP FOREIGN KEY `fk_invoice_student`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `spp_invoices` DROP FOREIGN KEY `fk_invoice_tariff_ref`');
            } catch (Throwable $e) {
            }
            try {
                DB::statement('ALTER TABLE `spp_invoices` DROP INDEX `ix_invoice_student_year_type`');
            } catch (Throwable $e) {
            }
        }

        // Drop enrollments, grades, academic_years.
        if (Schema::hasTable('enrollments')) {
            Schema::drop('enrollments');
        }

        if (Schema::hasTable('grades')) {
            Schema::drop('grades');
        }

        if (Schema::hasTable('academic_years')) {
            Schema::drop('academic_years');
        }
    }
};
