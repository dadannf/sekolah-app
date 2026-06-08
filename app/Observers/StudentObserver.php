<?php

namespace App\Observers;

use App\Events\StudentCreated;
use App\Events\StudentDeleted;
use App\Events\StudentUpdated;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class StudentObserver
{
    public function created(Student $student): void
    {
        StudentCreated::dispatch($student);
    }

    public function updated(Student $student): void
    {
        $changes = $student->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            Log::info('[StudentObserver] Dispatching StudentUpdated event', [
                'student_id' => $student->id,
                'student_name' => $student->name,
                'changes' => $changes,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            StudentUpdated::dispatch($student, $changes);
        }
    }

    /**
     * Handle the Student "deleting" event.
     * 
     * Cascade delete semua related records sebelum student dihapus:
     * - spp_invoices (dan payments yang attached ke invoices tersebut)
     * - student_payments
     * - ocr_payment_receipts
     * - enrollments
     * 
     * Ini adalah safety layer di application level yang bekerja bersama
     * database cascade delete constraints untuk memastikan konsistensi data.
     */
    public function deleting(Student $student): void
    {
        $studentId = $student->id;
        
        Log::info('[StudentObserver] Cascade deleting related records for student', [
            'student_id' => $studentId,
            'student_name' => $student->name
        ]);

        try {
            // Hapus payments yang terkait dengan invoices siswa ini
            if (Schema::hasTable('payments') && Schema::hasTable('spp_invoices')) {
                $invoiceIds = \App\Models\SppInvoice::where('student_id', $studentId)
                    ->pluck('id')
                    ->toArray();

                if (!empty($invoiceIds)) {
                    $deletedPayments = \App\Models\Payment::whereIn('invoice_id', $invoiceIds)->delete();
                    Log::info('[StudentObserver] Deleted related payments', [
                        'student_id' => $studentId,
                        'payments_deleted' => $deletedPayments
                    ]);
                }
            }

            // Hapus student_payments
            if (Schema::hasTable('student_payments')) {
                $deletedStudentPayments = DB::table('student_payments')
                    ->where('student_id', $studentId)
                    ->delete();
                    
                Log::info('[StudentObserver] Deleted student_payments', [
                    'student_id' => $studentId,
                    'records_deleted' => $deletedStudentPayments
                ]);
            }

            // Hapus ocr_payment_receipts
            if (Schema::hasTable('ocr_payment_receipts')) {
                $deletedOcrReceipts = DB::table('ocr_payment_receipts')
                    ->where('student_id', $studentId)
                    ->delete();
                    
                Log::info('[StudentObserver] Deleted ocr_payment_receipts', [
                    'student_id' => $studentId,
                    'records_deleted' => $deletedOcrReceipts
                ]);
            }

            // Hapus spp_invoices - ini akan trigger cascade delete payments via FK constraint
            if (Schema::hasTable('spp_invoices')) {
                $deletedInvoices = \App\Models\SppInvoice::where('student_id', $studentId)->delete();
                
                Log::info('[StudentObserver] Deleted spp_invoices', [
                    'student_id' => $studentId,
                    'invoices_deleted' => $deletedInvoices
                ]);
            }

            // Hapus enrollments
            if (Schema::hasTable('enrollments')) {
                $deletedEnrollments = DB::table('enrollments')
                    ->where('student_id', $studentId)
                    ->delete();
                    
                Log::info('[StudentObserver] Deleted enrollments', [
                    'student_id' => $studentId,
                    'records_deleted' => $deletedEnrollments
                ]);
            }

            // Clear target_student_id di information table (daripada delete, set to null)
            if (Schema::hasTable('information') && Schema::hasColumn('information', 'target_student_id')) {
                $updatedInformation = DB::table('information')
                    ->where('target_student_id', $studentId)
                    ->update(['target_student_id' => null]);
                    
                Log::info('[StudentObserver] Cleared target_student_id in information', [
                    'student_id' => $studentId,
                    'records_updated' => $updatedInformation
                ]);
            }

        } catch (\Exception $e) {
            Log::error('[StudentObserver] Error during cascade delete', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleted(Student $student): void
    {
        // Hapus akun user siswa jika ada
        if ($student->user_id) {
            try {
                \App\Models\User::where('id', $student->user_id)->delete();
                Log::info('[StudentObserver] Deleted associated user account', [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id
                ]);
            } catch (\Exception $e) {
                Log::error('[StudentObserver] Error deleting user account', [
                    'student_id' => $student->id,
                    'user_id' => $student->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Dispatch event
        StudentDeleted::dispatch(
            $student->id,
            $student->name,
            $student->nisn ?? null
        );
    }
}

