<?php

namespace App\Listeners;

use App\Events\PaymentCreated;
use App\Models\Notification;
use App\Models\User;

class SavePaymentCreatedNotification
{
    public function handle(PaymentCreated $event): void
    {
        // Get all admin & kepala sekolah users to notify
        $admins = User::whereIn('role', ['admin', 'kepala_sekolah'])->pluck('id')->toArray();
        $studentName = $event->payment->invoice?->student?->name ?? 'Unknown';

        $adminData = [
            'payment_id' => $event->payment->id,
            'amount' => $event->payment->amount,
            'status' => $event->payment->status,
            'method' => $event->payment->method,
            'invoice_id' => $event->payment->invoice_id,
            'student_name' => $studentName,
            'payment_date' => $event->payment->payment_date,
        ];

        foreach ($admins as $adminId) {
            Notification::createIfMissing(
                [
                    'user_id' => $adminId,
                    'type' => 'payment',
                    'action' => 'created',
                    'data->payment_id' => $event->payment->id,
                ],
                [
                    'user_id' => $adminId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'Unknown User',
                    'type' => 'payment',
                    'action' => 'created',
                    'title' => 'Pembayaran Baru Diterima',
                    'message' => "Pembayaran sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " dari {$studentName} telah ditambahkan oleh " . (auth()?->user()?->name ?? 'System'),
                    'data' => $adminData,
                    'changes' => null,
                ]
            );
        }

        // Notify student if exists
        if ($event->payment->invoice?->student) {
            $studentUserId = $event->payment->invoice->student->user_id;
            $studentData = [
                'payment_id' => $event->payment->id,
                'amount' => $event->payment->amount,
                'status' => $event->payment->status,
                'method' => $event->payment->method,
                'payment_date' => $event->payment->payment_date,
            ];

            Notification::createIfMissing(
                [
                    'user_id' => $studentUserId,
                    'type' => 'payment',
                    'action' => 'created',
                    'data->payment_id' => $event->payment->id,
                ],
                [
                    'user_id' => $studentUserId,
                    'performed_by_id' => auth()?->user()?->id ?? null,
                    'performed_by_name' => auth()?->user()?->name ?? 'System',
                    'type' => 'payment',
                    'action' => 'created',
                    'title' => 'Pembayaran Anda Dicatat',
                    'message' => "Pembayaran sebesar Rp " . number_format($event->payment->amount, 0, ',', '.') . " telah dicatat oleh " . (auth()?->user()?->name ?? 'System'),
                    'data' => $studentData,
                    'changes' => null,
                ]
            );
        }
    }
}
