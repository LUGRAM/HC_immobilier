<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lease;
use Carbon\Carbon;

class NewLeaseRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected Lease $lease;

    /**
     * Create a new notification instance.
     */
    public function __construct(Lease $lease)
    {
        $this->lease = $lease;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        /** @var Carbon $startDate */
        $startDate = $this->lease->start_date;

        return (new MailMessage)
            ->subject('Nouvelle demande de bail')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Vous avez reçu une nouvelle demande de bail.')
            ->line('Locataire : ' . $this->lease->tenant->full_name)
            ->line('Bien : ' . $this->lease->property->title)
            ->line('Loyer mensuel : ' . number_format((float) $this->lease->monthly_rent, 0, ',', ' ') . ' FCFA')
            ->line('Date de début souhaitée : ' . $startDate->format('d/m/Y'))
            ->action('Examiner la demande', url('/landlord/leases/' . $this->lease->id))
            ->line('Veuillez approuver ou refuser cette demande.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        /** @var Carbon $startDate */
        $startDate = $this->lease->start_date;

        return [
            'lease_id' => $this->lease->id,
            'tenant_name' => $this->lease->tenant->full_name,
            'property_title' => $this->lease->property->title,
            'monthly_rent' => (float) $this->lease->monthly_rent,
            'start_date' => $startDate->toDateString(),
            'message' => 'Nouvelle demande de bail de ' . $this->lease->tenant->full_name,
        ];
    }
}