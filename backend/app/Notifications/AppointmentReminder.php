<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;

class AppointmentReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected Appointment $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel - Rendez-vous de visite demain')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Vous avez un rendez-vous de visite demain !')
            ->line('Bien : ' . $this->appointment->property->title)
            ->line('Adresse : ' . $this->appointment->property->address)
            ->line('Date et heure : ' . $this->appointment->scheduled_at->format('d/m/Y à H:i'))
            ->action('Voir les détails', url('/appointments/' . $this->appointment->id))
            ->line('À bientôt !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'property_title' => $this->appointment->property->title,
            'scheduled_at' => $this->appointment->scheduled_at->toDateTimeString(),
            'message' => 'Rappel: Visite demain à ' . $this->appointment->scheduled_at->format('H:i'),
        ];
    }
}