<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Appointment;

class AppointmentConfirmed extends Notification implements ShouldQueue
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
            ->subject('Rendez-vous confirmé')
            ->level('success')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre rendez-vous de visite a été confirmé !')
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
            'message' => 'Rendez-vous confirmé pour le ' . $this->appointment->scheduled_at->format('d/m/Y à H:i'),
        ];
    }
}