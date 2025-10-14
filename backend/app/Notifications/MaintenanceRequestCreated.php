<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\MaintenanceRequest;

class MaintenanceRequestCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected MaintenanceRequest $maintenanceRequest;

    /**
     * Create a new notification instance.
     */
    public function __construct(MaintenanceRequest $maintenanceRequest)
    {
        $this->maintenanceRequest = $maintenanceRequest;
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
        $priorityLabel = [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente'
        ][$this->maintenanceRequest->priority] ?? 'Moyenne';

        $categoryLabel = [
            'plumbing' => 'Plomberie',
            'electrical' => 'Électricité',
            'hvac' => 'Climatisation',
            'appliance' => 'Électroménager',
            'structural' => 'Structure',
            'other' => 'Autre'
        ][$this->maintenanceRequest->category] ?? 'Autre';

        return (new MailMessage)
            ->subject('Nouvelle demande de maintenance')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Une nouvelle demande de maintenance a été créée.')
            ->line('Bien : ' . $this->maintenanceRequest->property->title)
            ->line('Locataire : ' . $this->maintenanceRequest->tenant->full_name)
            ->line('Catégorie : ' . $categoryLabel)
            ->line('Priorité : ' . $priorityLabel)
            ->line('Problème : ' . $this->maintenanceRequest->title)
            ->action('Voir les détails', url('/landlord/maintenance/' . $this->maintenanceRequest->id))
            ->line('Merci de traiter cette demande rapidement.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'property_title' => $this->maintenanceRequest->property->title,
            'tenant_name' => $this->maintenanceRequest->tenant->full_name,
            'category' => $this->maintenanceRequest->category,
            'priority' => $this->maintenanceRequest->priority,
            'title' => $this->maintenanceRequest->title,
            'message' => 'Nouvelle demande de maintenance : ' . $this->maintenanceRequest->title,
        ];
    }
}