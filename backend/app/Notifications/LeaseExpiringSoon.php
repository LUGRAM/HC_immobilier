<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lease;
use Carbon\Carbon;

class LeaseExpiringSoon extends Notification implements ShouldQueue
{
    use Queueable;

    protected Lease $lease;
    protected int $daysRemaining;

    public function __construct(Lease $lease, int $daysRemaining)
    {
        $this->lease = $lease;
        $this->daysRemaining = $daysRemaining;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Carbon $endDate */
        $endDate = $this->lease->end_date;

        return (new MailMessage)
            ->subject('Votre bail arrive à expiration')
            ->level('warning')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre bail arrive à expiration dans ' . $this->daysRemaining . ' jours.')
            ->line('Bien : ' . $this->lease->property->title)
            ->line('Date de fin : ' . $endDate->format('d/m/Y'))
            ->action('Renouveler mon bail', url('/leases/' . $this->lease->id . '/renew'))
            ->line('Contactez votre propriétaire pour discuter du renouvellement.');
    }

    public function toArray(object $notifiable): array
    {
        /** @var Carbon $endDate */
        $endDate = $this->lease->end_date;

        return [
            'lease_id' => $this->lease->id,
            'property_title' => $this->lease->property->title,
            'end_date' => $endDate->toDateString(),
            'days_remaining' => $this->daysRemaining,
            'message' => 'Votre bail expire dans ' . $this->daysRemaining . ' jours',
        ];
    }
}