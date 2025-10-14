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
// JOB: ProcessPaymentWebhook
// ============================================
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 60;
    public $backoff = [10, 30, 60]; // Retry after 10s, 30s, 60s

    protected array $webhookData;

    public function __construct(array $webhookData)
    {
        $this->webhookData = $webhookData;
    }

    public function handle(\App\Services\PaymentService $paymentService): void
    {
        try {
            $paymentService->handleCinetpayWebhook($this->webhookData);
            
            Log::info("Webhook processed successfully", [
                'transaction_id' => $this->webhookData['cpm_trans_id'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error("Webhook processing failed", [
                'data' => $this->webhookData,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }
}