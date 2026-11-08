<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        // Password-reset emails link to the React SPA's reset page.
        ResetPassword::createUrlUsing(fn ($user, string $token) => url(
            '/app/reset-password/' . $token . '?email=' . urlencode($user->getEmailForPasswordReset())
        ));
    }
}
