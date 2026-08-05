<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * Single source of truth for permission rules.
 *
 * No other file may inspect $user->role directly. Add new roles and
 * abilities here only; everywhere else must call the gate/policy.
 */
class Authorization
{
    public static function register(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->role === UserRole::Owner ? true : null;
        });
    }
}
