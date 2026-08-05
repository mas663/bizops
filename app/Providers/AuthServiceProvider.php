<?php

namespace App\Providers;

use App\Support\Authorization;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Authorization::register();
    }
}
