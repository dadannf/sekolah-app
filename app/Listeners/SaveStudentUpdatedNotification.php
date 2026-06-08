<?php

namespace App\Listeners;

use App\Events\StudentUpdated;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SaveStudentUpdatedNotification
{
    public function handle(StudentUpdated $event): void
    {
        Log::info('[SaveStudentUpdatedNotification] Event handled', [
            'student_id' => $event->student->id,
            'student_name' => $event->student->name,
            'changes' => $event->changes,
        ]);

        // Get all admin & kepala sekolah users to notify
        $admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();
        $changedFields = array_keys($event->changes);
        $performedById = auth()?->user()?->id ?? null;
        $performedByName = auth()?->user()?->name ?? 'System';
        $changedFieldsText = implode(', ', $changedFields);

        Log::info('[SaveStudentUpdatedNotification] Admin count', [
            'admin_ids' => $admins,
            'admin_count' => count($admins),
        ]);

        $adminMessage = "Data siswa '{$event->student->name}' telah diperbarui oleh {$performedByName}. Perubahan: {$changedFieldsText}";
        $adminData = [
            'student_id' => $event->student->id,
            'nisn' => $event->student->nisn,
            'name' => $event->student->name,
            'grade_id' => $event->student->grade_id,
            'changed_fields' => $changedFields,
        ];
        $changes = $event->changes;

        foreach ($admins as $adminId) {
            Log::info('[SaveStudentUpdatedNotification] Creating notification for admin', [
                'admin_id' => $adminId,
            ]);

            // Prevent double notifications caused by double-fired events/observers.
            // Dedupe on the exact same payload within a short time window.
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'student',
                    'action' => 'updated',
                    'data->student_id' => $event->student->id,
                    'message' => $adminMessage,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => $performedById,
                    'performed_by_name' => $performedByName,
                    'type' => 'student',
                    'action' => 'updated',
                    'title' => 'Data Siswa Diperbarui',
                    'message' => $adminMessage,
                    'data' => $adminData,
                    'changes' => $changes,
                ],
                10
            );
        }

        // Notify the student themselves about changes
        if ($event->student->user_id) {
            Log::info('[SaveStudentUpdatedNotification] Creating notification for student', [
                'student_user_id' => $event->student->user_id,
                'student_name' => $event->student->name,
            ]);

            $studentMessage = "Data Anda telah diperbarui oleh {$performedByName}. Perubahan: {$changedFieldsText}";
            $studentData = [
                'student_id' => $event->student->id,
                'nisn' => $event->student->nisn,
                'name' => $event->student->name,
                'changed_fields' => $changedFields,
            ];

            Notification::createIfMissing(
                [
                    'user_id' => $event->student->user_id,
                    'type' => 'student',
                    'action' => 'updated',
                    'data->student_id' => $event->student->id,
                    'message' => $studentMessage,
                ],
                [
                    'user_id' => $event->student->user_id,
                    'performed_by_id' => $performedById,
                    'performed_by_name' => $performedByName,
                    'type' => 'student',
                    'action' => 'updated',
                    'title' => 'Data Anda Diperbarui',
                    'message' => $studentMessage,
                    'data' => $studentData,
                    'changes' => $changes,
                ],
                10
            );
            Log::info('[SaveStudentUpdatedNotification] Notification created for student');
        }
    }
}

