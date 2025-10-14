<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use App\Models\{Appointment, Invoice};
use App\Services\{NotificationService, InvoiceGeneratorService};
use Illuminate\Support\Facades\{Log, DB};


// ============================================
// JOB: CleanupOldNotifications
// ============================================
class CleanupOldNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 120;

    public function handle(): void
    {
        $daysToKeep = 90; // Garder 90 jours
        
        $deleted = DB::table('notifications')
                     ->where('created_at', '<', now()->subDays($daysToKeep))
                     ->where('read_at', '!=', null)
                     ->delete();

        Log::info("Old notifications cleaned up", ['deleted' => $deleted]);
    }
}