<?php

namespace App\Listeners;

use App\Events\PaymentStatusChanged;
use App\Models\Notification;
use App\Models\User;

class SavePaymentStatusChangedNotification
{
    public function handle(PaymentStatusChanged $event): void
    {
        $title = match($event->newStatus) {
            'verified' => 'Pembayaran Diverifikasi',
            'rejected' => 'Pembayaran Ditolak',
            default => 'Status Pembayaran Berubah',
        };

        $statusMessage = match($event->newStatus) {
            'verified' => "✓ Pembayaran sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " telah diverifikasi oleh " . (auth()?->user()?->name ?? 'System'),
            'rejected' => "✗ Pembayaran sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " ditolak oleh " . (auth()?->user()?->name ?? 'System'),
            default => "Status pembayaran berubah dari {$event->oldStatus} menjadi {$event->newStatus} oleh " . (auth()?->user()?->name ?? 'System'),
        };

        // Get all admin & kepala sekolah users to notify
        $admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();
        $studentName = $event->payment->invoice?->student?->name ?? 'Unknown';

        $adminData = [
            'payment_id' => $event->payment->id,
            'amount' => $event->payment->amount,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'method' => $event->payment->method,
            'student_name' => $studentName,
            'verified_date' => $event->payment->verified_date,
            'note' => $event->payment->note,
        ];

        $changes = [
            'status' => [$event->oldStatus, $event->newStatus],
        ];

        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'payment',
                    'action' => 'status_changed',
                    'data->payment_id' => $event->payment->id,
                    'data->old_status' => $event->oldStatus,
                    'data->new_status' => $event->newStatus,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                    'type' => 'payment',
                    'action' => 'status_changed',
                    'title' => $title,
                    'message' => $statusMessage . " (Siswa: {$studentName})",
                    'data' => $adminData,
                    'changes' => $changes,
                ]
            );
        }

        // Notify student if exists
        if ($event->payment->invoice?->student) {
            $studentStatusMessage = match($event->newStatus) {
                'verified' => "✓ Pembayaran Anda sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " telah diverifikasi oleh " . (auth()?->user()?->name ?? 'System'),
                'rejected' => "✗ Pembayaran Anda sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " ditolak karena: " . ($event->payment->note ?? 'Tidak ada keterangan'),
                default => "Status pembayaran Anda berubah menjadi {$event->newStatus}",
            };

            $studentUserId = $event->payment->invoice->student->user_id;
            $studentData = [
                'payment_id' => $event->payment->id,
                'amount' => $event->payment->amount,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'verified_date' => $event->payment->verified_date,
                'note' => $event->payment->note,
            ];

            Notification::createIfMissing(
                [
                    'user_id' => $studentUserId,
                    'type' => 'payment',
                    'action' => 'status_changed',
                    'data->payment_id' => $event->payment->id,
                    'data->old_status' => $event->oldStatus,
                    'data->new_status' => $event->newStatus,
                ],
                [
                    'user_id' => $studentUserId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'System',
                    'type' => 'payment',
                    'action' => 'status_changed',
                    'title' => $title,
                    'message' => $studentStatusMessage,
                    'data' => $studentData,
                    'changes' => $changes,
                ]
            );
        }
    }
}
