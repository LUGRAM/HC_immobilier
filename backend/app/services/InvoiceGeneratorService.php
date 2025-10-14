<?php

namespace App\Services;

use App\Models\{ Invoice, Lease};
use Illuminate\Support\Facades\{ Log};
use App\Notifications\{
    InvoiceGenerated,
};

// ============================================
// SERVICE: InvoiceGeneratorService
// ============================================
class InvoiceGeneratorService
{
    /**
     * Générer automatiquement la facture de loyer mensuelle
     */
    public function generateRentInvoice(Lease $lease): Invoice
    {
        $dueDate = now()->addDays(5); // À payer dans 5 jours
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'type' => 'rent',
            'description' => "Loyer du mois de " . $periodStart->translatedFormat('F Y'),
            'amount' => $lease->monthly_rent,
            'due_date' => $dueDate,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => 'pending',
        ]);

        // Notifier le locataire
        $lease->tenant->notify(new InvoiceGenerated($invoice));

        // Push notification
        app(NotificationService::class)->sendPushToUser(
            $lease->tenant,
            'Nouvelle facture',
            "Votre facture de loyer de {$invoice->amount} FCFA est disponible. À payer avant le {$dueDate->format('d/m/Y')}",
            [
                'type' => 'new_invoice',
                'invoice_id' => $invoice->id,
            ]
        );

        Log::info('Rent invoice generated', [
            'invoice_id' => $invoice->id,
            'lease_id' => $lease->id,
            'amount' => $invoice->amount
        ]);

        return $invoice;
    }

    /**
     * Générer une facture personnalisée (eau, électricité, autre)
     */
    public function createCustomInvoice(
        Lease $lease,
        string $type,
        float $amount,
        string $description,
        $dueDate
    ): Invoice {
        $invoice = Invoice::create([
            'lease_id' => $lease->id,
            'tenant_id' => $lease->tenant_id,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
            'due_date' => $dueDate,
            'status' => 'pending',
        ]);

        // Notifier le locataire
        $lease->tenant->notify(new InvoiceGenerated($invoice));

        return $invoice;
    }

    /**
     * Générer les factures mensuelles pour tous les baux actifs
     * À exécuter via un Job/Command mensuel
     */
    public function generateMonthlyInvoices(): int
    {
        $activeLeases = Lease::active()->get();
        $count = 0;

        foreach ($activeLeases as $lease) {
            // Vérifier si une facture n'existe pas déjà pour ce mois
            $existingInvoice = Invoice::where('lease_id', $lease->id)
                                     ->where('type', 'rent')
                                     ->whereYear('period_start', now()->year)
                                     ->whereMonth('period_start', now()->month)
                                     ->first();

            if (!$existingInvoice) {
                $this->generateRentInvoice($lease);
                $count++;
            }
        }

        Log::info("Monthly invoices generated: {$count}");
        return $count;
    }

    /**
     * Marquer les factures en retard
     */
    public function markOverdueInvoices(): int
    {
        $invoices = Invoice::pending()
                          ->where('due_date', '<', now())
                          ->get();

        $count = 0;

        foreach ($invoices as $invoice) {
            $invoice->update(['status' => 'overdue']);
            
            // Notifier le locataire
            $invoice->tenant->notify(new \App\Notifications\InvoiceOverdue($invoice));
            
            // Notifier le bailleur
            $invoice->lease->landlord->notify(new \App\Notifications\TenantInvoiceOverdue($invoice));

            $count++;
        }

        Log::info("Overdue invoices marked: {$count}");
        return $count;
    }
}