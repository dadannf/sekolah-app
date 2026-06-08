<?php

namespace App\Observers;

use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        UserCreated::dispatch($user);
    }

    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        unset($changes['updated_at']);

        if (!empty($changes)) {
            UserUpdated::dispatch($user, $changes);
        }
    }

    public function deleted(User $user): void
    {
        UserDeleted::dispatch(
            $user->id,
            $user->name,
            $user->email,
            $user->role
        );
    }
}
