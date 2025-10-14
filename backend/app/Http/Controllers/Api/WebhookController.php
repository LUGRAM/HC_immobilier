<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Notifications\PaymentConfirmed;

class WebhookController extends Controller
{
    /**
     * Traite le webhook de CinetPay
     * La signature est déjà vérifiée par le middleware
     */
    public function cinetpay(Request $request)
    {
        $transactionId = $request->input('cpm_trans_id');
        $status = $request->input('cpm_trans_status');
        $amount = (int) $request->input('cpm_amount');

        Log::info('Processing CinetPay webhook', [
            'transaction_id' => $transactionId,
            'status' => $status,
            'amount' => $amount
        ]);

        try {
            DB::beginTransaction();

            // Trouver le paiement
            $payment = Payment::where('transaction_id', $transactionId)->first();

            if (!$payment) {
                Log::error('Payment not found for transaction', [
                    'transaction_id' => $transactionId
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found'
                ], 404);
            }

            // Éviter le double traitement
            if ($payment->status === 'completed') {
                Log::info('Payment already processed', [
                    'payment_id' => $payment->id
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment already processed'
                ], 200);
            }

            // Vérifier le montant
            if ($payment->amount != $amount) {
                Log::error('Amount mismatch', [
                    'expected' => $payment->amount,
                    'received' => $amount,
                    'payment_id' => $payment->id
                ]);

                $payment->update([
                    'status' => 'failed',
                    'error_message' => 'Amount mismatch'
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Amount mismatch'
                ], 400);
            }

            // Traiter selon le statut
            if ($status === 'ACCEPTED' || $status === 'APPROVED') {
                $this->handleSuccessfulPayment($payment, $request);
            } elseif ($status === 'REFUSED' || $status === 'FAILED') {
                $this->handleFailedPayment($payment, $request);
            } else {
                Log::warning('Unknown payment status', [
                    'status' => $status,
                    'payment_id' => $payment->id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Traite un paiement réussi
     */
    protected function handleSuccessfulPayment(Payment $payment, Request $request)
    {
        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'payment_method' => $request->input('payment_method'),
            'operator_transaction_id' => $request->input('cpm_payid'),
            'metadata' => [
                'payment_date' => $request->input('cpm_payment_date'),
                'payment_time' => $request->input('cpm_payment_time'),
                'phone_number' => $request->input('cel_phone_num')
            ]
        ]);

        // Si c'est un paiement de rendez-vous
        if ($payment->appointment_id) {
            $appointment = Appointment::find($payment->appointment_id);
            
            if ($appointment) {
                $appointment->update([
                    'payment_status' => 'paid',
                    'status' => 'confirmed'
                ]);

                // Envoyer notification
                $appointment->user->notify(new PaymentConfirmed($payment, $appointment));
            }
        }

        // Si c'est un paiement de facture (loyer, eau, électricité)
        if ($payment->invoice_id) {
            $invoice = Invoice::find($payment->invoice_id);
            
            if ($invoice) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now()
                ]);

                // Envoyer notification
                $invoice->user->notify(new PaymentConfirmed($payment, null, $invoice));
            }
        }

        Log::info('Payment completed successfully', [
            'payment_id' => $payment->id,
            'amount' => $payment->amount
        ]);
    }

    /**
     * Traite un paiement échoué
     */
    protected function handleFailedPayment(Payment $payment, Request $request)
    {
        $errorMessage = $request->input('cpm_error_message', 'Payment failed');

        $payment->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'metadata' => [
                'error_code' => $request->input('cpm_result_code'),
                'error_message' => $errorMessage,
                'failed_at' => now()
            ]
        ]);

        // Mettre à jour le rendez-vous si applicable
        if ($payment->appointment_id) {
            $appointment = Appointment::find($payment->appointment_id);
            
            if ($appointment) {
                $appointment->update([
                    'payment_status' => 'failed'
                ]);
            }
        }

        Log::warning('Payment failed', [
            'payment_id' => $payment->id,
            'error' => $errorMessage
        ]);
    }
}