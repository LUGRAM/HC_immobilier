<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MaintenanceRequest;

class MaintenanceRequestUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    protected MaintenanceRequest $maintenanceRequest;
    protected string $updateType;

    /**
     * Create a new notification instance.
     */
    public function __construct(MaintenanceRequest $maintenanceRequest, string $updateType = 'status_changed')
    {
        $this->maintenanceRequest = $maintenanceRequest;
        $this->updateType = $updateType;
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
        $statusLabel = [
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée'
        ][$this->maintenanceRequest->status] ?? 'En attente';

        $message = (new MailMessage)
            ->subject('Mise à jour de votre demande de maintenance')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre demande de maintenance a été mise à jour.');

        if ($this->updateType === 'status_changed') {
            $message->line('Nouveau statut : ' . $statusLabel);
        }

        if ($this->maintenanceRequest->status === 'in_progress' && $this->maintenanceRequest->scheduled_date) {
            $message->line('Date d\'intervention : ' . $this->maintenanceRequest->scheduled_date->format('d/m/Y à H:i'));
        }

        if ($this->maintenanceRequest->status === 'completed') {
            $message->level('success');
            $message->line('La maintenance a été effectuée avec succès.');
            
            if ($this->maintenanceRequest->resolution_notes) {
                $message->line('Notes : ' . $this->maintenanceRequest->resolution_notes);
            }
        }

        if ($this->maintenanceRequest->status === 'cancelled') {
            $message->level('warning');
            $message->line('La demande a été annulée.');
        }

        $message->action('Voir les détails', url('/maintenance/' . $this->maintenanceRequest->id));

        return $message;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'property_title' => $this->maintenanceRequest->property->title,
            'status' => $this->maintenanceRequest->status,
            'update_type' => $this->updateType,
            'title' => $this->maintenanceRequest->title,
        ];

        // Message personnalisé selon le statut
        $statusMessages = [
            'pending' => 'Votre demande de maintenance est en attente',
            'in_progress' => 'Votre demande de maintenance est en cours de traitement',
            'completed' => 'Votre demande de maintenance a été complétée',
            'cancelled' => 'Votre demande de maintenance a été annulée',
        ];

        $data['message'] = $statusMessages[$this->maintenanceRequest->status] ?? 'Votre demande de maintenance a été mise à jour';

        if ($this->maintenanceRequest->scheduled_date) {
            $data['scheduled_date'] = $this->maintenanceRequest->scheduled_date->toDateTimeString();
        }

        if ($this->maintenanceRequest->completed_date) {
            $data['completed_date'] = $this->maintenanceRequest->completed_date->toDateTimeString();
        }

        return $data;
    }
}