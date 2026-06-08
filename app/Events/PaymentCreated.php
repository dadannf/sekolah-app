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

class PaymentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin'),
        ];

        // Jika ada siswa/user yang terkait, broadcast ke channel mereka juga
        if ($this->payment->invoice && $this->payment->invoice->student) {
            $channels[] = new PrivateChannel("student.{$this->payment->invoice->student->id}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'payment.created';
    }

    public function broadcastWith(): array
    {
        $user = Auth::user();
        $performedBy = $user ? $user->name : 'System';
        
        $invoiceInfo = $this->payment->invoice ? [
            'invoice_id' => $this->payment->invoice->id,
            'invoice_date' => $this->payment->invoice->date,
            'invoice_type' => $this->payment->invoice->type,
            'student_name' => $this->payment->invoice->student?->name,
        ] : [];

        return array_merge([
            'id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'status' => $this->payment->status,
            'method' => $this->payment->method,
            'payment_date' => $this->payment->payment_date,
            'performed_by_id' => $user?->id,
            'performed_by_name' => $performedBy,
            'message' => "Pembayaran baru sebesar Rp " . number_format($this->payment->amount, 0, ',', '.') . " telah ditambahkan oleh {$performedBy}",
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'action' => 'created',
        ], $invoiceInfo);
    }
}
