<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use App\Models\{Appointment, Setting};
use App\Notifications\AppointmentReminderNotification;
use Illuminate\Support\Facades\Log;

// ============================================
// JOB: SendAppointmentReminders
// ============================================
class SendAppointmentReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    public function handle(): void
    {
        Log::info('Starting appointment reminders job');

        $this->send24HourReminders();
        $this->send1HourReminders();

        Log::info('Finished appointment reminders job');
    }

    /**
     * Send 24-hour reminders
     */
    protected function send24HourReminders(): void
    {
        if (!Setting::get('reminder_24h_enabled', true)) {
            Log::info('24h reminders are disabled');
            return;
        }

        $appointments = Appointment::where('scheduled_at', '>=', now()->addHours(23))
            ->where('scheduled_at', '<=', now()->addHours(25))
            ->where('status', 'confirmed')
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', AppointmentReminderNotification::class)
                    ->where('data->reminder_type', '24h')
                    ->where('created_at', '>', now()->subHours(24));
            })
            ->with(['client', 'property'])
            ->get();

        $count = 0;
        foreach ($appointments as $appointment) {
            if ($appointment->client) {
                try {
                    $notification = new AppointmentReminderNotification($appointment, '24h');
                    $notification->send($appointment->client);
                    $count++;
                } catch (\Exception $e) {
                    Log::error('Failed to send 24h reminder', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info("Sent {$count} 24-hour reminders");
    }

    /**
     * Send 1-hour reminders
     */
    protected function send1HourReminders(): void
    {
        if (!Setting::get('reminder_1h_enabled', true)) {
            Log::info('1h reminders are disabled');
            return;
        }

        $appointments = Appointment::where('scheduled_at', '>=', now()->addMinutes(55))
            ->where('scheduled_at', '<=', now()->addMinutes(65))
            ->where('status', 'confirmed')
            ->whereDoesntHave('notifications', function ($query) {
                $query->where('type', AppointmentReminderNotification::class)
                    ->where('data->reminder_type', '1h')
                    ->where('created_at', '>', now()->subHour());
            })
            ->with(['client', 'property'])
            ->get();

        $count = 0;
        foreach ($appointments as $appointment) {
            if ($appointment->client) {
                try {
                    $notification = new AppointmentReminderNotification($appointment, '1h');
                    $notification->send($appointment->client);
                    $count++;
                } catch (\Exception $e) {
                    Log::error('Failed to send 1h reminder', [
                        'appointment_id' => $appointment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info("Sent {$count} 1-hour reminders");
    }
}
