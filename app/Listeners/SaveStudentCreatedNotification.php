<?php

namespace App\Listeners;

use App\Events\StudentCreated;
use App\Models\Notification;
use App\Models\User;

class SaveStudentCreatedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(StudentCreated $event): void
    {
        // Get all admin & kepala sekolah users to notify
        $admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();
        $performedByName = $event->student->user?->name ?? 'System';

        $data = [
            'student_id' => $event->student->id,
            'nisn' => $event->student->nisn,
            'name' => $event->student->name,
            'grade_id' => $event->student->grade_id,
            'user_id' => $event->student->user_id,
        ];
        
        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'student',
                    'action' => 'created',
                    'data->student_id' => $event->student->id,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => $event->student->user_id ?? null,
                    'performed_by_name' => $performedByName,
                    'type' => 'student',
                    'action' => 'created',
                    'title' => 'Siswa Baru Ditambahkan',
                    'message' => "Siswa '{$event->student->name}' (NISN: {$event->student->nisn}) telah ditambahkan oleh {$performedByName}",
                    'data' => $data,
                    'changes' => null,
                ]
            );
        }
    }
}
