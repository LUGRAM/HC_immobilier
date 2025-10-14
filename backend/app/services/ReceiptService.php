<?php

namespace App\Services;

use App\Models\{Appointment, Invoice};

use illuminate\Support\Facades\Storage;

// ============================================
// SERVICE: ReceiptService (Génération PDF)
// ============================================
class ReceiptService
{
    /**
     * Générer un reçu PDF pour une facture payée
     */
    public function generateInvoiceReceipt(Invoice $invoice): object
    {
        $pdf = app('dompdf.wrapper');
        
        $data = [
            'invoice' => $invoice->load(['lease.property', 'tenant', 'lease.landlord']),
            'payment' => $invoice->payments()->completed()->first(),
            'generated_at' => now(),
        ];

        $pdf->loadView('pdf.invoice-receipt', $data);
        
        $filename = "recu-{$invoice->invoice_number}.pdf";
        $path = "receipts/{$filename}";
        
        Storage::put($path, $pdf->output());

        return (object) [
            'path' => $path,
            'url' => Storage::url($path),
            'filename' => $filename,
        ];
    }

    /**
     * Générer un reçu pour un paiement de visite
     */
    public function generateAppointmentReceipt(Appointment $appointment): object
    {
        $pdf = app('dompdf.wrapper');
        
        $data = [
            'appointment' => $appointment->load(['property', 'client']),
            'payment' => $appointment->payment,
            'generated_at' => now(),
        ];

        $pdf->loadView('pdf.appointment-receipt', $data);
        
        $filename = "recu-visite-{$appointment->id}.pdf";
        $path = "receipts/{$filename}";
        
        Storage::put($path, $pdf->output());

        return (object) [
            'path' => $path,
            'url' => Storage::url($path),
            'filename' => $filename,
        ];
    }
}
