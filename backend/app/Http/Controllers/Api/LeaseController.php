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
    InvoiceGeneratorService
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Auth, Cache};
use Illuminate\Validation\ValidationException;

// ============================================
// CONTROLLER: LeaseController
// ============================================
class LeaseController extends Controller
{
    public function requestFromAppointment($appointmentId)
    {
        try {
            DB::beginTransaction();

            $appointment = Appointment::where('client_id', Auth::id())
                                     ->findOrFail($appointmentId);

            if (!$appointment->canCreateLease()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas créer de bail pour ce rendez-vous',
                ], 400);
            }

            $property = $appointment->property;

            $lease = Lease::create([
                'property_id' => $property->id,
                'tenant_id' => Auth::id(),
                'landlord_id' => $property->landlord_id,
                'appointment_id' => $appointment->id,
                'start_date' => now()->addDays(7), // Début dans 7 jours
                'monthly_rent' => $property->monthly_rent,
                'status' => 'pending_approval',
            ]);

            // Notifier le bailleur
            $property->landlord->notify(new \App\Notifications\NewLeaseRequest($lease));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Demande de bail envoyée avec succès. En attente de validation.',
                'data' => $lease,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du bail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getActiveLease(Request $request)
    {
        $lease = Lease::with(['property.images', 'landlord'])
                     ->where('tenant_id', $request->user()->id)
                     ->active()
                     ->first();

        if (!$lease) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bail actif trouvé',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $lease,
        ]);
    }

    public function approve($id, Request $request)
    {
        $lease = Lease::where('landlord_id', Auth::id())
                     ->findOrFail($id);

        if ($lease->status !== 'pending_approval') {
            return response()->json([
                'success' => false,
                'message' => 'Ce bail ne peut pas être approuvé',
            ], 400);
        }

        DB::transaction(function () use ($lease, $request) {
            $lease->approve($request->user());
            
            // Générer la première facture de loyer
            app(InvoiceGeneratorService::class)->generateRentInvoice($lease);
            
            // Notifier le locataire
            $lease->tenant->notify(new \App\Notifications\LeaseApproved($lease));
        });

        return response()->json([
            'success' => true,
            'message' => 'Bail approuvé avec succès',
        ]);
    }

    public function myTenants(Request $request)
    {
        $tenants = Lease::with(['tenant', 'property'])
                       ->where('landlord_id', $request->user()->id)
                       ->active()
                       ->get()
                       ->pluck('tenant')
                       ->unique('id');

        return response()->json([
            'success' => true,
            'data' => $tenants,
        ]);
    }
}
