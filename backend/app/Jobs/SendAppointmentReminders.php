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
// JOB: SendAppointmentReminders
// ============================================
class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function handle(NotificationService $notificationService): void
    {
        // Récupérer les rendez-vous nécessitant un rappel
        $appointments = Appointment::needingReminder()->get();

        Log::info("Processing appointment reminders", ['count' => $appointments->count()]);

        foreach ($appointments as $appointment) {
            try {
                $notificationService->sendAppointmentReminder($appointment);
                
                Log::info("Reminder sent", [
                    'appointment_id' => $appointment->id,
                    'client_id' => $appointment->client_id
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to send reminder", [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
