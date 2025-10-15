<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Appointment;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Payment Service for CinetPay Integration
 * 
 * This service handles all payment operations through CinetPay API
 * Configure credentials in config/services.php:
 * 
 * 'cinetpay' => [
 *     'api_key' => env('CINETPAY_API_KEY'),
 *     'site_id' => env('CINETPAY_SITE_ID'),
 *     'secret_key' => env('CINETPAY_SECRET_KEY'),
 *     'mode' => env('CINETPAY_MODE', 'TEST'), // TEST or PRODUCTION
 * ]
 */
class PaymentService
{
    protected $apiUrl;
    protected $apiKey;
    protected $siteId;
    protected $secretKey;

    public function __construct()
    {
        $mode = config('services.cinetpay.mode', 'TEST');
        
        $this->apiUrl = $mode === 'PRODUCTION' 
            ? 'https://api-checkout.cinetpay.com/v2/'
            : 'https://api-checkout.cinetpay.com/v2/'; // Same URL for test
        
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->secretKey = config('services.cinetpay.secret_key');
    }

    /**
     * Initiate a payment for an appointment
     *
     * @param Appointment $appointment
     * @return array
     */
    public function initiateAppointmentPayment(Appointment $appointment): array
    {
        $user = $appointment->client;
        $property = $appointment->property;
        $amount = config('settings.visit_price', 5000);

        $payment = Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'currency' => config('settings.currency', 'XOF'),
            'transaction_id' => $this->generateTransactionId(),
            'payment_method' => 'mobile_money',
            'status' => 'pending',
            'payable_type' => Appointment::class,
            'payable_id' => $appointment->id,
            'description' => "Rendez-vous de visite - {$property->title}",
        ]);

        return $this->initiatePayment(
            $payment->amount,
            $payment->transaction_id,
            $payment->description,
            [
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]
        );
    }

    /**
     * Initiate a general payment
     *
     * @param float $amount
     * @param string $transactionId
     * @param string $description
     * @param array $customer
     * @return array
     */
    public function initiatePayment(float $amount, string $transactionId, string $description, array $customer): array
    {
        try {
            $data = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $transactionId,
                'amount' => (int) $amount,
                'currency' => config('settings.currency', 'XOF'),
                'description' => $description,
                'customer_name' => $customer['name'] ?? '',
                'customer_surname' => $customer['name'] ?? '',
                'customer_email' => $customer['email'] ?? '',
                'customer_phone_number' => $customer['phone'] ?? '',
                'customer_address' => $customer['address'] ?? '',
                'customer_city' => $customer['city'] ?? '',
                'customer_country' => $customer['country'] ?? 'CI',
                'customer_state' => $customer['state'] ?? '',
                'customer_zip_code' => $customer['zip'] ?? '',
                'notify_url' => route('api.payment.webhook'),
                'return_url' => route('api.payment.success'),
                'channels' => 'ALL', // Accept all payment methods
                'metadata' => json_encode([
                    'transaction_id' => $transactionId,
                    'customer_id' => $customer['id'] ?? null,
                ]),
            ];

            $response = Http::post($this->apiUrl . 'payment', $data);

            if ($response->successful()) {
                $result = $response->json();
                
                if (isset($result['code']) && $result['code'] == '201') {
                    return [
                        'success' => true,
                        'payment_url' => $result['data']['payment_url'],
                        'payment_token' => $result['data']['payment_token'],
                        'transaction_id' => $transactionId,
                    ];
                }
            }

            Log::error('CinetPay initiate payment failed', [
                'response' => $response->body(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => 'Unable to initiate payment. Please try again.',
            ];

        } catch (\Exception $e) {
            Log::error('CinetPay payment exception', [
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => 'Payment service error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a payment status
     *
     * @param string $transactionId
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            $data = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $transactionId,
            ];

            $response = Http::post($this->apiUrl . 'payment/check', $data);

            if ($response->successful()) {
                $result = $response->json();
                
                return [
                    'success' => true,
                    'status' => $result['data']['status'] ?? 'PENDING',
                    'amount' => $result['data']['amount'] ?? 0,
                    'currency' => $result['data']['currency'] ?? 'XOF',
                    'payment_method' => $result['data']['payment_method'] ?? null,
                    'operator_id' => $result['data']['operator_id'] ?? null,
                    'data' => $result['data'] ?? [],
                ];
            }

            return [
                'success' => false,
                'message' => 'Unable to verify payment',
            ];

        } catch (\Exception $e) {
            Log::error('CinetPay verify payment exception', [
                'message' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'message' => 'Verification error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment webhook callback
     *
     * @param array $data
     * @return bool
     */
    public function handleWebhook(array $data): bool
    {
        try {
            $transactionId = $data['cpm_trans_id'] ?? null;
            
            if (!$transactionId) {
                Log::warning('Webhook received without transaction ID', $data);
                return false;
            }

            $verification = $this->verifyPayment($transactionId);

            if (!$verification['success']) {
                Log::warning('Webhook verification failed', [
                    'transaction_id' => $transactionId,
                    'verification' => $verification,
                ]);
                return false;
            }

            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::warning('Payment not found for webhook', [
                    'transaction_id' => $transactionId,
                ]);
                return false;
            }

            $status = $verification['data']['status'] ?? 'PENDING';
            
            if ($status === 'ACCEPTED' || $status === 'SUCCESSFUL') {
                $payment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'payment_method' => $verification['data']['payment_method'] ?? 'mobile_money',
                    'payment_details' => json_encode($verification['data']),
                ]);

                if ($payment->payable_type === Appointment::class) {
                    $appointment = $payment->payable;
                    if ($appointment) {
                        $appointment->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                    }
                }

                $payment->user->notify(new \App\Notifications\PaymentConfirmedNotification($payment));

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Webhook handling exception', [
                'message' => $e->getMessage(),
                'data' => $data,
            ]);

            return false;
        }
    }

    /**
     * Generate a unique transaction ID
     *
     * @return string
     */
    protected function generateTransactionId(): string
    {
        return 'HC-' . time() . '-' . Str::random(8);
    }

    /**
     * Check if CinetPay is configured
     *
     * @return bool
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->siteId) && !empty($this->secretKey);
    }
}
