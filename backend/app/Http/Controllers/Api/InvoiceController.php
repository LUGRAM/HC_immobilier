<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{
    RegisterRequest,
    LoginRequest,
    CreateAppointmentRequest,
    CreateExpenseRequest
};
use App\Http\Resources\{
    UserResource,
    PropertyResource,
    AppointmentResource,
    InvoiceResource,
    ExpenseResource,
    DashboardResource
};
use App\Models\{
    User,
    Property,
    Appointment,
    Lease,
    Invoice,
    Payment,
    Expense,
    VisitSetting,
    DeviceToken
};
use App\Services\{
    PaymentService,
    NotificationService,
    InvoiceGeneratorService,
    ReceiptService
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Auth, Cache};
use Illuminate\Validation\ValidationException;

// ============================================
// CONTROLLER: InvoiceController
// ============================================
class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['lease.property'])
                       ->where('tenant_id', $request->user()->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->forType($request->type);
        }

        $invoices = $query->orderBy('due_date', 'desc')
                         ->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function show($id)
    {
        $invoice = Invoice::with(['lease.property', 'payments'])
                         ->where('tenant_id', Auth::id())
                         ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function initiatePayment($id, Request $request, PaymentService $paymentService)
    {
        $request->validate([
            'phone_number' => 'required|string',
        ]);

        $invoice = Invoice::where('tenant_id', Auth::id())
                         ->findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture est déjà payée',
            ], 400);
        }

        try {
            $paymentResult = $paymentService->initiateInvoicePayment(
                $invoice,
                $request->phone_number
            );

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié avec succès',
                'data' => $paymentResult,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function landlordInvoices(Request $request)
    {
        $query = Invoice::with(['tenant', 'lease.property'])
                       ->whereHas('lease', function($q) use ($request) {
                           $q->where('landlord_id', $request->user()->id);
                       });

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $invoices = $query->orderBy('due_date', 'desc')
                         ->paginate(15);

        return InvoiceResource::collection($invoices);
    }

    public function createForTenant(Request $request, InvoiceGeneratorService $invoiceService)
    {
        $request->validate([
            'lease_id' => 'required|exists:leases,id',
            'type' => 'required|in:water,electricity,other',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'due_date' => 'required|date|after:today',
        ]);

        try {
            $lease = Lease::where('landlord_id', Auth::id())
                         ->findOrFail($request->lease_id);

            $invoice = $invoiceService->createCustomInvoice(
                $lease,
                $request->type,
                $request->amount,
                $request->description,
                $request->due_date
            );

            // Notifier le locataire
            $lease->tenant->notify(new \App\Notifications\NewInvoice($invoice));

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès',
                'data' => new InvoiceResource($invoice),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'description' => 'sometimes|string',
        ]);

        $invoice = Invoice::whereHas('lease', function($q) use ($request) {
                         $q->where('landlord_id', $request->user()->id);
                     })
                     ->findOrFail($id);

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Une facture payée ne peut pas être modifiée',
            ], 400);
        }

        $invoice->update($request->only(['amount', 'due_date', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Facture mise à jour avec succès',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function getReceipt($id)
    {
        $invoice = Invoice::with(['lease.property', 'tenant', 'payments'])
                         ->where('tenant_id', Auth::id())
                         ->findOrFail($id);

        if ($invoice->status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Aucun reçu disponible pour cette facture',
            ], 400);
        }

        // Générer PDF du reçu
        $pdf = app(ReceiptService::class)->generateInvoiceReceipt($invoice);

        return response()->json([
            'success' => true,
            'data' => [
                'invoice' => new InvoiceResource($invoice),
                'receipt_url' => $pdf->getUrl(),
            ],
        ]);
    }
}