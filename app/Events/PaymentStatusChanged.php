<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class PaymentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin'),
        ];

        // Broadcast ke siswa jika ada
        if ($this->payment->invoice && $this->payment->invoice->student) {
            $channels[] = new PrivateChannel("student.{$this->payment->invoice->student->id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'payment.status.changed';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';

        $statusMessage = match($this->newStatus) {
            'verified' => "✓ Pembayaran sebesar Rp " . number_format($this->payment->amount, 0, ',', '.') . " telah diverifikasi oleh {$performedBy}",
            'rejected' => "✗ Pembayaran sebesar Rp " . number_format($this->payment->amount, 0, ',', '.') . " ditolak oleh {$performedBy}",
            default => "Status pembayaran berubah dari {$this->oldStatus} menjadi {$this->newStatus} oleh {$performedBy}"
        };

        return [
            'id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'method' => $this->payment->method,
            'payment_date' => $this->payment->payment_date,
            'verified_by' => $this->payment->verified_by,
            'verified_date' => $this->payment->verified_date,
            'note' => $this->payment->note,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => $statusMessage,
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'status_changed',
        ];
    }
}
