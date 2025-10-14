<?php

// ============================================
// PROVIDER: app/Providers/AuthServiceProvider.php
// ============================================
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\{Appointment, Lease, Invoice};
use App\Policies\{AppointmentPolicy, LeasePolicy, InvoicePolicy};

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Appointment::class => AppointmentPolicy::class,
        Lease::class => LeasePolicy::class,
        Invoice::class => InvoicePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}


