<?php

namespace App\Services;

use App\Models\{Payment, Appointment, Invoice};
use Illuminate\Support\Facades\{Http, Log, DB};
use App\Notifications\{
    PaymentReceived,
};

// ============================================
// SERVICE: PaymentService (CinetPay)
// ============================================
class PaymentService
{
    protected string $apiKey;
    protected string $siteId;
    protected string $secretKey;
    protected string $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.cinetpay.api_key');
        $this->siteId = config('services.cinetpay.site_id');
        $this->secretKey = config('services.cinetpay.secret_key');
        $this->apiUrl = config('services.cinetpay.api_url', 'https://api-checkout.cinetpay.com/v2/payment');
    }

    /**
     * Initier un paiement pour une visite
     */
    public function initiateVisitPayment(Appointment $appointment, string $phoneNumber): array
    {
        try {
            DB::beginTransaction();

            // Créer un enregistrement de paiement
            $payment = Payment::create([
                'user_id' => $appointment->client_id,
                'payable_type' => Appointment::class,
                'payable_id' => $appointment->id,
                'amount' => $appointment->amount_paid,
                'type' => 'visit',
                'method' => 'mobile_money',
                'status' => 'pending',
                'provider' => 'cinetpay',
                'phone_number' => $phoneNumber,
            ]);

            // Préparer la requête CinetPay
            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $payment->transaction_id,
                'amount' => (int) $appointment->amount_paid,
                'currency' => 'XOF', // Franc CFA
                'description' => "Paiement visite bien #{$appointment->property_id}",
                'customer_name' => $appointment->client->full_name,
                'customer_surname' => $appointment->client->last_name,
                'customer_email' => $appointment->client->email,
                'customer_phone_number' => $phoneNumber,
                'notify_url' => route('api.webhooks.cinetpay'),
                'return_url' => config('app.url') . '/payment/success',
                'channels' => 'ALL', // MOBILE_MONEY, CREDIT_CARD, etc.
                'metadata' => json_encode([
                    'appointment_id' => $appointment->id,
                    'property_id' => $appointment->property_id,
                ]),
            ];

            // Appeler l'API CinetPay
            $response = Http::timeout(30)
                           ->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                throw new \Exception('Erreur lors de l\'initiation du paiement CinetPay');
            }

            $data = $response->json();

            if ($data['code'] !== '201') {
                throw new \Exception($data['message'] ?? 'Erreur de paiement');
            }

            // Mettre à jour le paiement avec les infos CinetPay
            $payment->update([
                'provider_transaction_id' => $data['data']['payment_token'],
                'provider_response' => $data,
                'status' => 'processing',
            ]);

            DB::commit();

            return [
                'transaction_id' => $payment->transaction_id,
                'payment_url' => $data['data']['payment_url'],
                'payment_token' => $data['data']['payment_token'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment initiation failed', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Initier un paiement pour une facture
     */
    public function initiateInvoicePayment(Invoice $invoice, string $phoneNumber): array
    {
        try {
            DB::beginTransaction();

            $payment = Payment::create([
                'user_id' => $invoice->tenant_id,
                'payable_type' => Invoice::class,
                'payable_id' => $invoice->id,
                'amount' => $invoice->amount,
                'type' => $invoice->type,
                'method' => 'mobile_money',
                'status' => 'pending',
                'provider' => 'cinetpay',
                'phone_number' => $phoneNumber,
            ]);

            $payload = [
                'apikey' => $this->apiKey,
                'site_id' => $this->siteId,
                'transaction_id' => $payment->transaction_id,
                'amount' => (int) $invoice->amount,
                'currency' => 'XOF',
                'description' => "Paiement facture {$invoice->invoice_number} - {$invoice->description}",
                'customer_name' => $invoice->tenant->full_name,
                'customer_surname' => $invoice->tenant->last_name,
                'customer_email' => $invoice->tenant->email,
                'customer_phone_number' => $phoneNumber,
                'notify_url' => route('api.webhooks.cinetpay'),
                'return_url' => config('app.url') . '/payment/success',
                'channels' => 'ALL',
                'metadata' => json_encode([
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'lease_id' => $invoice->lease_id,
                ]),
            ];

            $response = Http::timeout(30)->post($this->apiUrl, $payload);

            if (!$response->successful()) {
                throw new \Exception('Erreur lors de l\'initiation du paiement');
            }

            $data = $response->json();

            if ($data['code'] !== '201') {
                throw new \Exception($data['message'] ?? 'Erreur de paiement');
            }

            $payment->update([
                'provider_transaction_id' => $data['data']['payment_token'],
                'provider_response' => $data,
                'status' => 'processing',
            ]);

            DB::commit();

            return [
                'transaction_id' => $payment->transaction_id,
                'payment_url' => $data['data']['payment_url'],
                'payment_token' => $data['data']['payment_token'],
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Invoice payment initiation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Gérer le webhook CinetPay
     */
    public function handleCinetpayWebhook(array $data): bool
    {
        try {
            DB::beginTransaction();

            $transactionId = $data['cpm_trans_id'] ?? null;
            
            if (!$transactionId) {
                throw new \Exception('Transaction ID manquant');
            }

            // Vérifier le statut auprès de CinetPay
            $checkResponse = $this->checkPaymentStatus($transactionId);

            if ($checkResponse['code'] !== '00') {
                Log::warning('Payment verification failed', $checkResponse);
                return false;
            }

            $paymentData = $checkResponse['data'];
            $customTransactionId = $paymentData['metadata'];

            // Trouver le paiement
            $payment = Payment::where('transaction_id', $customTransactionId)->first();

            if (!$payment) {
                throw new \Exception("Payment not found: {$customTransactionId}");
            }

            // Mettre à jour selon le statut
            if ($paymentData['payment_status'] === 'ACCEPTED') {
                $payment->markAsCompleted();
                
                // Mettre à jour l'entité payable
                $payable = $payment->payable;
                
                if ($payable instanceof Appointment) {
                    $payable->markAsPaid($transactionId, 'mobile_money');
                    
                    // Notifier le client
                    $payable->client->notify(new PaymentReceived($payment));
                    
                } elseif ($payable instanceof Invoice) {
                    $payable->markAsPaid($transactionId, 'mobile_money');
                    
                    // Notifier le locataire et le bailleur
                    $payable->tenant->notify(new PaymentReceived($payment));
                    $payable->lease->landlord->notify(new PaymentReceived($payment));
                }

                Log::info('Payment completed successfully', [
                    'transaction_id' => $payment->transaction_id,
                    'amount' => $payment->amount
                ]);

            } elseif ($paymentData['payment_status'] === 'REFUSED') {
                $payment->markAsFailed('Paiement refusé par le provider');
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Webhook handling failed', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(string $cinetpayTransactionId): array
    {
        $response = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', [
            'apikey' => $this->apiKey,
            'site_id' => $this->siteId,
            'transaction_id' => $cinetpayTransactionId,
        ]);

        return $response->json();
    }

    /**
     * Vérifier la signature du webhook
     */
    public function verifyCinetpaySignature($request): bool
    {
        $receivedSignature = $request->header('X-CinetPay-Signature');
        $payload = $request->getContent();
        
        $computedSignature = hash_hmac('sha256', $payload, $this->secretKey);
        
        return hash_equals($computedSignature, $receivedSignature);
    }
}