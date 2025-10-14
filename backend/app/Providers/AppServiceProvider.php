<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use illuminate\Support\Facades\URL;

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
        // ============================================
        // RATE LIMITING PERSONNALISÉ
        // ============================================
        
        // Limite API générale: 60 requêtes par minute
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les endpoints publics
        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Limite pour les paiements (plus restrictif)
        RateLimiter::for('payments', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        // Limite pour les uploads
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // ============================================
        // OBSERVERS (enregistrement automatique)
        // ============================================
        \App\Models\User::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Property::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Appointment::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Lease::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Invoice::observe(\App\Observers\ActivityLogObserver::class);
        \App\Models\Payment::observe(\App\Observers\ActivityLogObserver::class);

        // ============================================
        // FORCER HTTPS EN PRODUCTION
        // ============================================
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}