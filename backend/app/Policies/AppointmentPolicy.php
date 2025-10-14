<?php

// ============================================
// POLICY: app/Policies/AppointmentPolicy.php
// ============================================
namespace App\Policies;

use App\Models\{User, Appointment};

class AppointmentPolicy
{
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->client_id 
            || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isClient();
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->client_id 
            && in_array($appointment->status, ['pending_payment', 'paid']);
    }
}