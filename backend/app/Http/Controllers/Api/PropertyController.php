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
// CONTROLLER: PropertyController
// ============================================
class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::with(['landlord', 'primaryImage'])
                        ->available();

        // Filtres
        if ($request->has('district')) {
            $query->inDistrict($request->district);
        }

        if ($request->has('min_price') && $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        if ($request->has('bedrooms')) {
            $query->withBedrooms($request->bedrooms);
        }

        if ($request->has('property_type')) {
            $query->where('property_type', $request->property_type);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $properties = $query->orderBy('created_at', 'desc')
                           ->paginate($request->get('per_page', 15));

        return PropertyResource::collection($properties);
    }

    public function show($id)
    {
        $property = Property::with([
            'landlord',
            'images',
            'currentLease.tenant'
        ])->findOrFail($id);

        // Incrémenter les vues
        $property->incrementViews();

        return response()->json([
            'success' => true,
            'data' => new PropertyResource($property),
        ]);
    }

    public function getDistricts()
    {
        $districts = Property::select('district')
                            ->distinct()
                            ->whereNotNull('district')
                            ->orderBy('district')
                            ->pluck('district');

        return response()->json([
            'success' => true,
            'data' => $districts,
        ]);
    }

    public function getVisitSettings()
    {
        $settings = VisitSetting::current();

        return response()->json([
            'success' => true,
            'data' => [
                'visit_price' => $settings->visit_price,
                'available_time_slots' => $settings->available_time_slots,
            ],
        ]);
    }

    public function myProperties(Request $request)
    {
        $properties = Property::where('landlord_id', $request->user()->id)
                             ->with(['images', 'currentLease.tenant'])
                             ->withCount('appointments')
                             ->orderBy('created_at', 'desc')
                             ->paginate(15);

        return PropertyResource::collection($properties);
    }
}