<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lease;

class LeaseRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected Lease $lease;
    protected ?string $reason;

    public function __construct(Lease $lease, ?string $reason = null)
    {
        $this->lease = $lease;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Demande de bail refusée')
            ->level('error')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre demande de bail a été refusée.')
            ->line('Bien : ' . $this->lease->property->title);

        if ($this->reason) {
            $message->line('Raison : ' . $this->reason);
        }

        return $message
            ->action('Voir d\'autres biens', url('/properties'))
            ->line('Nous vous invitons à consulter nos autres biens disponibles.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'lease_id' => $this->lease->id,
            'property_title' => $this->lease->property->title,
            'reason' => $this->reason,
            'message' => 'Votre demande de bail a été refusée',
        ];
    }
}