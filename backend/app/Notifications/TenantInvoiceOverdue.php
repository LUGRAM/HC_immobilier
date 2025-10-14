<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;
use Carbon\Carbon;

class TenantInvoiceOverdue extends Notification implements ShouldQueue
{
    use Queueable;

    protected Invoice $invoice;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invoice $invoice)
    {
        $this->invoice = $invoice;
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
        /** @var Carbon $dueDate */
        $dueDate = $this->invoice->due_date;

        return (new MailMessage)
            ->subject('Alerte - Facture impayée d\'un locataire')
            ->level('warning')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Un de vos locataires a une facture en retard.')
            ->line('Locataire : ' . $this->invoice->tenant->full_name)
            ->line('Numéro de facture : ' . $this->invoice->invoice_number)
            ->line('Montant : ' . number_format((float) $this->invoice->amount, 0, ',', ' ') . ' FCFA')
            ->line('Date d\'échéance : ' . $dueDate->format('d/m/Y'))
            ->action('Voir les détails', url('/landlord/invoices/' . $this->invoice->id))
            ->line('Merci de faire le suivi nécessaire.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {

        /** @var Carbon $dueDate */
        $dueDate = $this->invoice->due_date;
        
        return [
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'tenant_name' => $this->invoice->tenant->full_name,
            'amount' => $this->invoice->amount,
            'type' => $this->invoice->type,
            'due_date' => $dueDate->toDateString(),
            'message' => 'Facture en retard pour ' . $this->invoice->tenant->full_name,
        ];
    }
}