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
// JOB: GenerateMonthlyInvoices
// ============================================
class GenerateMonthlyInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;
    public $timeout = 300;

    public function handle(InvoiceGeneratorService $invoiceService): void
    {
        Log::info("Starting monthly invoice generation");

        try {
            $count = $invoiceService->generateMonthlyInvoices();
            
            Log::info("Monthly invoices generated successfully", ['count' => $count]);

        } catch (\Exception $e) {
            Log::error("Monthly invoice generation failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
