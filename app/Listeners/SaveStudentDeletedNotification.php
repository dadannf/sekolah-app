<?php

namespace App\Listeners;

use App\Events\StudentDeleted;
use App\Models\Notification;
use App\Models\User;

class SaveStudentDeletedNotification
{
    public function handle(StudentDeleted $event): void
    {
        // Get all admin & kepala sekolah users to notify
        $admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();

        $data = [
            'student_id' => $event->studentId,
            'nisn' => $event->nisn,
            'name' => $event->studentName,
        ];

        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'student',
                    'action' => 'deleted',
                    'data->student_id' => $event->studentId,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                    'type' => 'student',
                    'action' => 'deleted',
                    'title' => 'Siswa Dihapus',
                    'message' => "Siswa '{$event->studentName}' (NISN: {$event->nisn}) telah dihapus dari sistem oleh " . (auth()?->user()?->name ?? 'System'),
                    'data' => $data,
                    'changes' => null,
                ]
            );
        }
    }
}
