<?php

namespace App\Policies;

use App\Models\Lease;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// ============================================
// POLICY: app/Policies/LeasePolicy.php
// ============================================
class LeasePolicy
{
    public function view(User $user, Lease $lease): bool
    {
        return $user->id === $lease->tenant_id 
            || $user->id === $lease->landlord_id 
            || $user->isAdmin();
    }

    public function approve(User $user, Lease $lease): bool
    {
        return ($user->id === $lease->landlord_id || $user->isAdmin())
            && $lease->status === 'pending_approval';
    }

    public function terminate(User $user, Lease $lease): bool
    {
        return ($user->id === $lease->landlord_id || $user->isAdmin())
            && $lease->status === 'active';
    }
}