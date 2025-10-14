<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceOverdue extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Facture en retard - ' . $this->invoice->invoice_number)
            ->error()
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre facture de ' . $this->invoice->typeLabel . ' est en retard.')
            ->line('Numéro de facture : ' . $this->invoice->invoice_number)
            ->line('Montant : ' . number_format((float) $this->invoice->amount, 0, ',', ' ') . ' FCFA')
            ->line('Date \'échéance : ' . Carbon::parse($this->invoice->due_date)->format('d/m/Y'))
            ->action('Payer maintenant', url('/invoices/' . $this->invoice->id))
            ->line('Merci de régulariser votre situation dans les plus brefs délais.');
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
            'amount' => $this->invoice->amount,
            'type' => $this->invoice->type,
            'due_date' => $dueDate->toDateString(),
            'message' => 'Votre facture ' . $this->invoice->invoice_number . ' est en retard',
        ];
    }
}