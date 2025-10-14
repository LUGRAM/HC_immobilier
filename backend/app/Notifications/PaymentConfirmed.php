<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Payment;

class PaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    protected Payment $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Paiement confirmé')
            ->level('success')
            ->greeting('Bonjour ' . $notifiable->full_name)
            ->line('Votre paiement a été confirmé avec succès !')
            ->line('Montant : ' . number_format((float) $this->payment->amount, 0, ',', ' ') . ' FCFA')
            ->line('Référence : ' . $this->payment->transaction_id)
            ->action('Télécharger le reçu', url('/payments/' . $this->payment->id . '/receipt'))
            ->line('Merci pour votre paiement !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => (float) $this->payment->amount,
            'message' => 'Paiement confirmé : ' . number_format((float) $this->payment->amount, 0, ',', ' ') . ' FCFA',
        ];
    }
}