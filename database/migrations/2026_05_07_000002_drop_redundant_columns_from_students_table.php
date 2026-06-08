<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns that are redundant, duplicated, or better stored in a separate profile table.
     */
    private array $columnsToDrop = [
        'telephone',
        'mobile_phone',
        'father_job',
        'mother_job',
        'weight_kg',
        'height_cm',
        'waist_cm',
        'distance_to_school',
        'travel_time',
        'siblings_count',
        'previous_school',
        'hobby',
        'aspiration',
        'birth_certificate_registration_no',
        'shirt_size',
        'diploma_serial_no',
        'previous_school_npsn',
        'notes',
        'transportation',
        'residence_type',
        'father_income',
        'mother_income',
    ];

    public function up(): void
    {
        if (Schema::hasTable('students')) {
            $this->normalizePhoneNumber();

            Schema::table('students', function (Blueprint $table) {
                foreach ($this->columnsToDrop as $column) {
                    if (Schema::hasColumn('students', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('students')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'telephone')) {
                $table->string('telephone', 30)->nullable()->after('transportation');
            }

            if (!Schema::hasColumn('students', 'mobile_phone')) {
                $table->string('mobile_phone', 30)->nullable()->after('telephone');
            }

            if (!Schema::hasColumn('students', 'father_job')) {
                $table->string('father_job', 80)->nullable()->after('father_education');
            }

            if (!Schema::hasColumn('students', 'mother_job')) {
                $table->string('mother_job', 80)->nullable()->after('mother_education');
            }

            if (!Schema::hasColumn('students', 'weight_kg')) {
                $table->decimal('weight_kg', 5, 2)->nullable()->after('mother_income');
            }

            if (!Schema::hasColumn('students', 'height_cm')) {
                $table->decimal('height_cm', 5, 2)->nullable()->after('weight_kg');
            }

            if (!Schema::hasColumn('students', 'waist_cm')) {
                $table->decimal('waist_cm', 5, 2)->nullable()->after('height_cm');
            }

            if (!Schema::hasColumn('students', 'distance_to_school')) {
                $table->string('distance_to_school', 50)->nullable()->after('waist_cm');
            }

            if (!Schema::hasColumn('students', 'travel_time')) {
                $table->string('travel_time', 50)->nullable()->after('distance_to_school');
            }

            if (!Schema::hasColumn('students', 'siblings_count')) {
                $table->tinyInteger('siblings_count')->nullable()->after('travel_time');
            }

            if (!Schema::hasColumn('students', 'previous_school')) {
                $table->string('previous_school', 150)->nullable()->after('siblings_count');
            }

            if (!Schema::hasColumn('students', 'hobby')) {
                $table->string('hobby', 100)->nullable()->after('previous_school');
            }

            if (!Schema::hasColumn('students', 'aspiration')) {
                $table->string('aspiration', 100)->nullable()->after('hobby');
            }

            if (!Schema::hasColumn('students', 'birth_certificate_registration_no')) {
                $table->string('birth_certificate_registration_no', 80)->nullable()->after('aspiration');
            }

            if (!Schema::hasColumn('students', 'shirt_size')) {
                $table->string('shirt_size', 20)->nullable()->after('birth_certificate_registration_no');
            }

            if (!Schema::hasColumn('students', 'diploma_serial_no')) {
                $table->string('diploma_serial_no', 80)->nullable()->after('shirt_size');
            }

            if (!Schema::hasColumn('students', 'previous_school_npsn')) {
                $table->string('previous_school_npsn', 20)->nullable()->after('diploma_serial_no');
            }

            if (!Schema::hasColumn('students', 'notes')) {
                $table->text('notes')->nullable()->after('previous_school_npsn');
            }

            if (!Schema::hasColumn('students', 'transportation')) {
                $table->string('transportation', 50)->nullable()->after('residence_type');
            }

            if (!Schema::hasColumn('students', 'residence_type')) {
                $table->string('residence_type', 50)->nullable()->after('address_postal_code');
            }

            if (!Schema::hasColumn('students', 'father_income')) {
                $table->string('father_income', 50)->nullable()->after('father_job');
            }

            if (!Schema::hasColumn('students', 'mother_income')) {
                $table->string('mother_income', 50)->nullable()->after('mother_job');
            }
        });
    }

    private function normalizePhoneNumber(): void
    {
        // Only normalize if all the necessary columns exist
        if (!Schema::hasColumn('students', 'phone_number') ||
            !Schema::hasColumn('students', 'mobile_phone') ||
            !Schema::hasColumn('students', 'telephone')) {
            return;
        }

        DB::table('students')
            ->where(function ($query) {
                $query->whereNull('phone_number')
                    ->orWhere('phone_number', '');
            })
            ->update([
                'phone_number' => DB::raw(
                    "COALESCE(NULLIF(phone_number, ''), NULLIF(mobile_phone, ''), NULLIF(telephone, ''))"
                ),
            ]);
    }
};