<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Lease;
use Carbon\Carbon;

class LeaseApproved extends Notification implements ShouldQueue
{
    use Queueable;

    protected Lease $lease;

    public function __construct(Lease $lease)
    {
        $this->lease = $lease;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Carbon $startDate */
        $startDate = $this->lease->start_date; // ✅ start_date au lieu de due_date
        
        return (new MailMessage)
            ->subject('Bail approuvé !')
            ->level('success') // ✅ level('success') au lieu de success()
            ->greeting('Félicitations ' . $notifiable->full_name . ' !')
            ->line('Votre demande de bail a été approuvée.')
            ->line('Bien : ' . $this->lease->property->title)
            ->line('Loyer mensuel : ' . number_format((float) $this->lease->monthly_rent, 0, ',', ' ') . ' FCFA')
            ->line('Date de début : ' . $startDate->format('d/m/Y')) // ✅ Utilise la variable
            ->action('Voir mon bail', url('/leases/' . $this->lease->id))
            ->line('Bienvenue dans votre nouveau logement !');
    }

    public function toArray(object $notifiable): array
    {
        /** @var Carbon $startDate */
        $startDate = $this->lease->start_date; // ✅ Cast explicite
        
        return [
            'lease_id' => $this->lease->id,
            'property_title' => $this->lease->property->title,
            'monthly_rent' => (float) $this->lease->monthly_rent, // ✅ Cast en float
            'start_date' => $startDate->toDateString(), // ✅ Utilise la variable
            'message' => 'Votre bail a été approuvé !',
        ];
    }
}