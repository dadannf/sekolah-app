<?php

namespace App\Listeners;

use App\Events\UserUpdated;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;

class SaveUserUpdatedNotification
{
    public function handle(UserUpdated $event): void
    {
        // Skip notification if this is a student user being updated via student model
        // (StudentUpdated listener already handles notifications for students)
        if ($event->user->role === 'siswa') {
            return;
        }

        // Get all admin to notify
        $admins = User::where('role', 'admin')->pluck('id')->toArray();
        $changedFields = array_keys($event->changes);

        $data = [
            'user_id' => $event->user->id,
            'email' => $event->user->email,
            'name' => $event->user->name,
            'role' => $event->user->role,
        ];

        foreach ($admins as $adminId) {
            if ($adminId !== $event->user->id) { // Don't notify the user being updated
                Notification::createIfMissing(
                    [
                        'user_id' => $adminId,
                        'type' => 'user',
                        'action' => 'updated',
                        'data->user_id' => $event->user->id,
                    ],
                    [
                        'user_id' => $adminId,
                        'performed_by_id' => auth()?->user()?->id ?? null,
                        'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                        'type' => 'user',
                        'action' => 'updated',
                        'title' => 'Data Pengguna Diperbarui',
                        'message' => "Data pengguna '{$event->user->name}' telah diperbarui oleh " . (auth()?->user()?->name ?? 'System') . ". Perubahan: " . implode(', ', $changedFields),
                        'data' => $data,
                        'changes' => $event->changes,
                    ],
                    5
                );
            }
        }

        // Notify the user being updated (only if not a student)
        Notification::createIfMissing(
            [
                'user_id' => $event->user->id,
                'type' => 'user',
                'action' => 'updated',
                'data->user_id' => $event->user->id,
            ],
            [
                'user_id' => $event->user->id,
                'performed_by_id' => auth()?->user()?->id ?? null,
                'performed_by_name' => auth()?->user()?->name ?? 'System',
                'type' => 'user',
                'action' => 'updated',
                'title' => 'Data Anda Diperbarui',
                'message' => "Data Anda telah diperbarui oleh " . (auth()?->user()?->name ?? 'System') . ". Perubahan: " . implode(', ', $changedFields),
                'data' => $data,
                'changes' => $event->changes,
            ],
            5
        );
    }
}

