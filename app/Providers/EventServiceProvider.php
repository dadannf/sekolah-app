<?php

namespace App\Providers;

use App\Events\PaymentCreated;
use App\Events\PaymentStatusChanged;
use App\Events\StudentCreated;
use App\Events\StudentDeleted;
use App\Events\StudentUpdated;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\SavePaymentCreatedNotification;
use App\Listeners\SavePaymentStatusChangedNotification;
use App\Listeners\SaveStudentCreatedNotification;
use App\Listeners\SaveStudentDeletedNotification;
use App\Listeners\SaveStudentUpdatedNotification;
use App\Listeners\SaveUserCreatedNotification;
use App\Listeners\SaveUserDeletedNotification;
use App\Listeners\SaveUserUpdatedNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        StudentCreated::class => [
            SaveStudentCreatedNotification::class,
        ],
        StudentUpdated::class => [
            SaveStudentUpdatedNotification::class,
        ],
        StudentDeleted::class => [
            SaveStudentDeletedNotification::class,
        ],
        PaymentCreated::class => [
            SavePaymentCreatedNotification::class,
        ],
        PaymentStatusChanged::class => [
            SavePaymentStatusChangedNotification::class,
        ],
        UserCreated::class => [
            SaveUserCreatedNotification::class,
        ],
        UserUpdated::class => [
            SaveUserUpdatedNotification::class,
        ],
        UserDeleted::class => [
            SaveUserDeletedNotification::class,
        ],
    ];

    /**
     * Discover and register event listeners.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
