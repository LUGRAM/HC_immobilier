<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PaymentConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Send push notification
     */
    public function send($notifiable)
    {
        $pushService = app(PushNotificationService::class);
        
        $pushService->sendToUser(
            $notifiable,
            'Paiement confirmé',
            "Votre paiement de {$this->payment->amount} {$this->payment->currency} a été confirmé avec succès",
            [
                'type' => 'payment_confirmed',
                'payment_id' => $this->payment->id,
                'transaction_id' => $this->payment->transaction_id,
                'amount' => $this->payment->amount,
            ]
        );

        $notifiable->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => static::class,
            'data' => $this->toArray($notifiable),
            'read_at' => null,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_confirmed',
            'payment_id' => $this->payment->id,
            'transaction_id' => $this->payment->transaction_id,
            'amount' => $this->payment->amount,
            'currency' => $this->payment->currency,
            'description' => $this->payment->description,
            'completed_at' => $this->payment->completed_at?->toDateTimeString(),
            'message' => "Votre paiement de {$this->payment->amount} {$this->payment->currency} a été confirmé",
            'title' => 'Paiement confirmé',
        ];
    }
}
