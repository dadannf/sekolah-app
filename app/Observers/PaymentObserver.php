<?php

namespace App\Observers;

use App\Events\PaymentCreated;
use App\Events\PaymentStatusChanged;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        PaymentCreated::dispatch($payment);
    }

    public function updated(Payment $payment): void
    {
        // Check if status has changed
        if ($payment->isDirty('status')) {
            $oldStatus = $payment->getOriginal('status');
            $newStatus = $payment->status;

            PaymentStatusChanged::dispatch(
                $payment,
                $oldStatus,
                $newStatus
            );
        }
    }
}
