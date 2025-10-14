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
// CONTROLLER: DashboardController
// ============================================
class DashboardController extends Controller
{
    public function clientDashboard(Request $request)
    {
        $user = $request->user();

        $activeLease = Lease::with('property')
                           ->where('tenant_id', $user->id)
                           ->active()
                           ->first();

        if (!$activeLease) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun bail actif. Veuillez d\'abord valider une visite.',
                'data' => [
                    'has_active_lease' => false,
                    'upcoming_appointments' => Appointment::where('client_id', $user->id)
                                                         ->upcoming()
                                                         ->with('property')
                                                         ->get(),
                ],
            ]);
        }

        $data = [
            'has_active_lease' => true,
            'lease' => $activeLease,
            'pending_invoices' => Invoice::where('tenant_id', $user->id)
                                        ->pending()
                                        ->with('lease.property')
                                        ->get(),
            'overdue_invoices' => Invoice::where('tenant_id', $user->id)
                                        ->overdue()
                                        ->count(),
            'total_expenses_month' => Expense::where('user_id', $user->id)
                                            ->inMonth(now()->year, now()->month)
                                            ->sum('amount'),
            'recent_payments' => Payment::where('user_id', $user->id)
                                            ->completed()
                                            ->orderBy('completed_at', 'desc')
                                            ->limit(5)
                                            ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function landlordDashboard(Request $request)
    {
        $landlord = $request->user();

        $data = [
            'total_properties' => Property::where('landlord_id', $landlord->id)->count(),
            'rented_properties' => Property::where('landlord_id', $landlord->id)->rented()->count(),
            'available_properties' => Property::where('landlord_id', $landlord->id)->available()->count(),
            'active_tenants' => Lease::where('landlord_id', $landlord->id)->active()->count(),
            'pending_leases' => Lease::where('landlord_id', $landlord->id)
                                    ->pendingApproval()
                                    ->with(['tenant', 'property'])
                                    ->get(),
            'monthly_revenue' => $this->calculateMonthlyRevenue($landlord->id),
            'recent_payments' => Payment::whereHas('payable', function($q) use ($landlord) {
                                        $q->whereHas('lease', function($query) use ($landlord) {
                                            $query->where('landlord_id', $landlord->id);
                                        });
                                    })
                                    ->completed()
                                    ->orderBy('completed_at', 'desc')
                                    ->limit(10)
                                    ->get(),
            'overdue_invoices' => Invoice::whereHas('lease', function($q) use ($landlord) {
                                        $q->where('landlord_id', $landlord->id);
                                    })
                                    ->overdue()
                                    ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function calculateMonthlyRevenue($landlordId)
    {
        return Payment::whereHas('payable', function($q) use ($landlordId) {
                    $q->whereHas('lease', function($query) use ($landlordId) {
                        $query->where('landlord_id', $landlordId);
                    });
                })
                ->where('status', 'completed')
                ->whereYear('completed_at', now()->year)
                ->whereMonth('completed_at', now()->month)
                ->sum('amount');
    }
}
