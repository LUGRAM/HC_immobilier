<?php

namespace App\Services;

use App\Models\{ Appointment, User};
use Illuminate\Support\Facades\{Http, Log};
use App\Notifications\{
    AppointmentReminder,
};

// ============================================
// SERVICE: NotificationService (OneSignal)
// ============================================
class NotificationService
{
    protected string $appId;
    protected string $apiKey;

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.api_key');
    }

    /**
     * Envoyer une notification push à un utilisateur
     */
    public function sendPushToUser(User $user, string $title, string $message, array $data = []): void
    {
        $deviceTokens = $user->deviceTokens()->pluck('token')->toArray();

        if (empty($deviceTokens)) {
            Log::info('No device tokens found for user', ['user_id' => $user->id]);
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://onesignal.com/api/v1/notifications', [
                'app_id' => $this->appId,
                'include_player_ids' => $deviceTokens,
                'headings' => ['en' => $title, 'fr' => $title],
                'contents' => ['en' => $message, 'fr' => $message],
                'data' => $data,
            ]);

            if (!$response->successful()) {
                Log::error('OneSignal push failed', [
                    'user_id' => $user->id,
                    'response' => $response->json()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Push notification error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer un SMS OTP
     */
    public function sendOtp(User $user, string $otp): void
    {
        // Intégration avec un provider SMS (ex: Twilio, InfoBip, etc.)
        // Pour l'exemple, on log simplement
        Log::info('OTP sent', [
            'phone' => $user->phone,
            'otp' => $otp
        ]);

        // Exemple avec Twilio:
        // $twilio = new Client(config('services.twilio.sid'), config('services.twilio.token'));
        // $twilio->messages->create($user->phone, [
        //     'from' => config('services.twilio.from'),
        //     'body' => "Votre code de vérification est: {$otp}"
        // ]);
    }

    /**
     * Envoyer un rappel de rendez-vous
     */
    public function sendAppointmentReminder(Appointment $appointment): void
    {
        $user = $appointment->client;
        
        // Notification Laravel
        $user->notify(new AppointmentReminder($appointment));

        // Push notification
        $this->sendPushToUser(
            $user,
            'Rappel de rendez-vous',
            "Votre visite est prévue demain à {$appointment->scheduled_at->format('H:i')} pour {$appointment->property->title}",
            [
                'type' => 'appointment_reminder',
                'appointment_id' => $appointment->id,
                'property_id' => $appointment->property_id,
            ]
        );

        // Marquer le rappel comme envoyé
        $appointment->update(['reminder_sent_at' => now()]);
    }
}