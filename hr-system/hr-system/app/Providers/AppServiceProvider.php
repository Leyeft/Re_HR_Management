<?php

namespace App\Providers;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
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

        // In production every link Laravel generates (assets, emails, signed URLs) uses https.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Every sign-in: remember when/where (shown on profiles) and add it to the activity log.
        // saveQuietly so this bookkeeping isn't logged as an "edit" of the user.
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ])->saveQuietly();

            ActivityLog::create([
                'user_id' => $event->user->getAuthIdentifier(),
                'action' => 'login',
                'subject_type' => 'User',
                'subject_id' => $event->user->getAuthIdentifier(),
                'description' => "{$event->user->name} signed in",
                'ip_address' => request()->ip(),
            ]);
        });

        // Password-reset emails link to the React SPA's reset page.
        ResetPassword::createUrlUsing(fn ($user, string $token) => url(
            '/reset-password/' . $token . '?email=' . urlencode($user->getEmailForPasswordReset())
        ));
    }
}
