<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Invoice;
use Carbon\Carbon;

class InvoiceGenerated extends Notification implements ShouldQueue
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
            ->subject('Nouvelle facture - ' . $this->invoice->invoice_number)
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Une nouvelle facture a été générée pour votre compte.')
            ->line('Type : ' . $this->invoice->typeLabel)
            ->line('Montant : ' . number_format((float) $this->invoice->amount, 0, ',', ' ') . ' FCFA')
            ->line('Date d\'échéance : ' . Carbon::parse($this->invoice->due_date)->format('d/m/Y'))
            ->action('Voir la facture', url('/invoices/' . $this->invoice->id))
            ->line('Merci de votre confiance !');
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
            'message' => 'Nouvelle facture ' . $this->invoice->typeLabel . ' - ' . number_format((float) $this->invoice->amount, 0, ',', ' ') . ' FCFA',
        ];
    }
}