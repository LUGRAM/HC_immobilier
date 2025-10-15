<?php

namespace App\Notifications;

use App\Models\Appointment;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $appointment;
    protected $reminderType; // '24h' or '1h'

    /**
     * Create a new notification instance.
     */
    public function __construct(Appointment $appointment, string $reminderType = '24h')
    {
        $this->appointment = $appointment;
        $this->reminderType = $reminderType;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Send push notification
     */
    public function send($notifiable)
    {
        $pushService = app(PushNotificationService::class);
        
        $timeText = $this->reminderType === '24h' ? '24 heures' : '1 heure';
        
        $pushService->sendToUser(
            $notifiable,
            'Rappel de rendez-vous',
            "Votre visite de {$this->appointment->property->title} est dans {$timeText}",
            [
                'type' => 'appointment_reminder',
                'appointment_id' => $this->appointment->id,
                'property_id' => $this->appointment->property_id,
                'reminder_type' => $this->reminderType,
            ]
        );

        $notifiable->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => static::class,
            'data' => $this->toArray($notifiable),
            'read_at' => null,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $timeText = $this->reminderType === '24h' ? '24 heures' : '1 heure';
        
        return [
            'type' => 'appointment_reminder',
            'reminder_type' => $this->reminderType,
            'appointment_id' => $this->appointment->id,
            'property_id' => $this->appointment->property_id,
            'property_title' => $this->appointment->property->title,
            'property_address' => $this->appointment->property->address,
            'scheduled_at' => $this->appointment->scheduled_at->toDateTimeString(),
            'message' => "Votre rendez-vous pour visiter {$this->appointment->property->title} est dans {$timeText}",
            'title' => 'Rappel de rendez-vous',
        ];
    }
}
