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
// CONTROLLER: AppointmentController
// ============================================
class AppointmentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(CreateAppointmentRequest $request)
    {
        try {
            DB::beginTransaction();

            $property = Property::findOrFail($request->property_id);
            
            if (!$property->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce bien n\'est plus disponible',
                ], 400);
            }

            $visitSettings = VisitSetting::current();

            $appointment = Appointment::create([
                'client_id' => $request->user()->getKey(),
                'property_id' => $property->id,
                'scheduled_at' => $request->scheduled_at,
                'status' => 'pending_payment',
                'amount_paid' => $visitSettings->visit_price,
                'client_notes' => $request->notes,
            ]);

            // Initier le paiement
            $paymentResult = $this->paymentService->initiateVisitPayment(
                $appointment,
                $request->phone_number
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rendez-vous créé. Veuillez procéder au paiement.',
                'data' => [
                    'appointment' => new AppointmentResource($appointment),
                    'payment' => $paymentResult,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du rendez-vous',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Appointment::with(['property.primaryImage', 'property.landlord'])
                           ->where('client_id', $request->user()->getKey()); // ✅ Utilise getKey()

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('upcoming') && $request->upcoming) {
            $query->upcoming();
        }

        $appointments = $query->orderBy('scheduled_at', 'desc')
                             ->paginate(15);

        return AppointmentResource::collection($appointments);
    }

    public function show($id)
    {
        $appointment = Appointment::with(['property.images', 'property.landlord', 'payment'])
                                 ->where('client_id', Auth::id()) // ✅ Utilise Auth::id()
                                 ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new AppointmentResource($appointment),
        ]);
    }

    public function cancel($id)
    {
        $appointment = Appointment::where('client_id', Auth::id()) // ✅ Utilise Auth::id()
                                 ->findOrFail($id);

        if (!in_array($appointment->status, ['pending_payment', 'paid'])) {
            return response()->json([
                'success' => false,
                'message' => 'Ce rendez-vous ne peut pas être annulé',
            ], 400);
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Rendez-vous annulé avec succès',
        ]);
    }
}