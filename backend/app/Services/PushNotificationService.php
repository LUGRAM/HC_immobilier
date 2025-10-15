<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Push Notification Service for OneSignal Integration
 * 
 * Configure credentials in config/services.php:
 * 
 * 'onesignal' => [
 *     'app_id' => env('ONESIGNAL_APP_ID'),
 *     'api_key' => env('ONESIGNAL_API_KEY'),
 * ]
 */
class PushNotificationService
{
    protected $appId;
    protected $apiKey;
    protected $apiUrl = 'https://onesignal.com/api/v1';

    public function __construct()
    {
        $this->appId = config('services.onesignal.app_id');
        $this->apiKey = config('services.onesignal.api_key');
    }

    /**
     * Send notification to a specific user
     *
     * @param User $user
     * @param string $title
     * @param string $message
     * @param array $data Additional data to send
     * @return array
     */
    public function sendToUser(User $user, string $title, string $message, array $data = []): array
    {
        if (!$this->isConfigured()) {
            Log::warning('OneSignal not configured');
            return ['success' => false, 'message' => 'Push notifications not configured'];
        }

        $playerIds = $user->deviceTokens()->pluck('token')->toArray();

        if (empty($playerIds)) {
            Log::info('No device tokens for user', ['user_id' => $user->id]);
            return ['success' => false, 'message' => 'No device tokens found'];
        }

        return $this->send([
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title, 'fr' => $title],
            'contents' => ['en' => $message, 'fr' => $message],
            'data' => $data,
        ]);
    }

    /**
     * Send notification to multiple users
     *
     * @param array $userIds
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public function sendToUsers(array $userIds, string $title, string $message, array $data = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Push notifications not configured'];
        }

        $playerIds = \App\Models\DeviceToken::whereIn('user_id', $userIds)
            ->pluck('token')
            ->toArray();

        if (empty($playerIds)) {
            return ['success' => false, 'message' => 'No device tokens found'];
        }

        return $this->send([
            'include_player_ids' => $playerIds,
            'headings' => ['en' => $title, 'fr' => $title],
            'contents' => ['en' => $message, 'fr' => $message],
            'data' => $data,
        ]);
    }

    /**
     * Send notification to a segment
     *
     * @param string $segment Segment name (e.g., 'All', 'Active Users', 'Landlords', 'Clients')
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public function sendToSegment(string $segment, string $title, string $message, array $data = []): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'Push notifications not configured'];
        }

        return $this->send([
            'included_segments' => [$segment],
            'headings' => ['en' => $title, 'fr' => $title],
            'contents' => ['en' => $message, 'fr' => $message],
            'data' => $data,
        ]);
    }

    /**
     * Send notification to users by role
     *
     * @param string $role
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public function sendToRole(string $role, string $title, string $message, array $data = []): array
    {
        $userIds = User::where('role', $role)->pluck('id')->toArray();
        return $this->sendToUsers($userIds, $title, $message, $data);
    }

    /**
     * Send notification to all users
     *
     * @param string $title
     * @param string $message
     * @param array $data
     * @return array
     */
    public function sendToAll(string $title, string $message, array $data = []): array
    {
        return $this->sendToSegment('All', $title, $message, $data);
    }

    /**
     * Send the notification via OneSignal API
     *
     * @param array $payload
     * @return array
     */
    protected function send(array $payload): array
    {
        try {
            $payload['app_id'] = $this->appId;

            $payload = array_merge([
                'ios_badgeType' => 'Increase',
                'ios_badgeCount' => 1,
                'android_channel_id' => config('app.name', 'HouseConnect'),
            ], $payload);

            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/notifications', $payload);

            if ($response->successful()) {
                $result = $response->json();
                
                Log::info('Push notification sent successfully', [
                    'notification_id' => $result['id'] ?? null,
                    'recipients' => $result['recipients'] ?? 0,
                ]);

                return [
                    'success' => true,
                    'notification_id' => $result['id'] ?? null,
                    'recipients' => $result['recipients'] ?? 0,
                ];
            }

            Log::error('OneSignal API error', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $response->json(),
            ];

        } catch (\Exception $e) {
            Log::error('Push notification exception', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Register a device token for a user
     *
     * @param User $user
     * @param string $token
     * @param string $platform ('ios' or 'android')
     * @return bool
     */
    public function registerDeviceToken(User $user, string $token, string $platform): bool
    {
        try {
            $user->deviceTokens()->updateOrCreate(
                ['token' => $token],
                [
                    'platform' => $platform,
                    'is_active' => true,
                ]
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to register device token', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Unregister a device token
     *
     * @param string $token
     * @return bool
     */
    public function unregisterDeviceToken(string $token): bool
    {
        try {
            \App\Models\DeviceToken::where('token', $token)->delete();
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to unregister device token', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Check if OneSignal is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->apiKey);
    }

    /**
     * Get notification statistics
     *
     * @param string $notificationId
     * @return array
     */
    public function getNotificationStats(string $notificationId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $this->apiKey,
            ])->get($this->apiUrl . "/notifications/{$notificationId}?app_id={$this->appId}");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get notification stats',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ];
        }
    }
}
