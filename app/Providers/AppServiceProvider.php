<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Observers\PaymentObserver;
use App\Observers\StudentObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Model Observers for real-time notifications
        Student::observe(StudentObserver::class);
        User::observe(UserObserver::class);
        Payment::observe(PaymentObserver::class);
    }
}
