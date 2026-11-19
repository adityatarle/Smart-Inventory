<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        view()->composer('*', function ($view) {
            if (Auth::check()) {
                Auth::user()->loadMissing('roles.permissions');
            }
        });
    }
}
