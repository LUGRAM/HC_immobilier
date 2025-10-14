<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use App\Models\{Appointment, Invoice};
use App\Services\{NotificationService, InvoiceGeneratorService};
use Illuminate\Support\Facades\Log;

// ============================================
// JOB: CleanupExpiredDeviceTokens
// ============================================
class CleanupExpiredDeviceTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 60;

    public function handle(): void
    {
        $daysInactive = 60;
        
        $deleted = \App\Models\DeviceToken::where('last_used_at', '<', now()->subDays($daysInactive))
                                         ->orWhereNull('last_used_at')
                                         ->where('created_at', '<', now()->subDays($daysInactive))
                                         ->delete();

        Log::info("Expired device tokens cleaned up", ['deleted' => $deleted]);
    }
}
