<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Auth\Access\Response;

// ============================================
// POLICY: app/Policies/InvoicePolicy.php
// ============================================
class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->tenant_id 
            || $user->id === $invoice->lease->landlord_id 
            || $user->isAdmin();
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->id === $invoice->tenant_id 
            && $invoice->status !== 'paid';
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return ($user->id === $invoice->lease->landlord_id || $user->isAdmin())
            && $invoice->status !== 'paid';
    }
}
