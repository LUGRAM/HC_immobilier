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
    DeviceToken,
    ActivityLog
};
use App\Services\{
    PaymentService,
    NotificationService,
    InvoiceGeneratorService
};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, DB, Auth, Cache, Log};
use Illuminate\Validation\ValidationException;


// ============================================
// CONTROLLER: PaymentController
// ============================================
class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function history(Request $request)
    {
        $query = Payment::where('user_id', $request->user()->id)
                       ->with('payable');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->forType($request->type);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $payments = $query->orderBy('created_at', 'desc')
                         ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function show($transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)
                         ->where('user_id', Auth::id())
                         ->with('payable')
                         ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $payment,
        ]);
    }

    public function receivedPayments(Request $request)
    {
        // Pour les bailleurs : paiements reçus via leurs baux
        $query = Payment::whereHasMorph('payable', [Invoice::class], function($q) use ($request) {
                        $q->whereHas('lease', function($query) use ($request) {
                            $query->where('landlord_id', $request->user()->id);
                        });
                    })
                    ->with(['user', 'payable'])
                    ->completed();

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('completed_at', [
                $request->start_date,
                $request->end_date
            ]);
        }

        $payments = $query->orderBy('completed_at', 'desc')
                        ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function paymentsSummary(Request $request)
    {
        $landlordId = $request->user()->id;

        $data = [
            'today' => $this->calculateRevenue($landlordId, 'today'),
            'this_week' => $this->calculateRevenue($landlordId, 'week'),
            'this_month' => $this->calculateRevenue($landlordId, 'month'),
            'this_year' => $this->calculateRevenue($landlordId, 'year'),
            'by_method' => $this->getPaymentsByMethod($landlordId),
            'by_type' => $this->getPaymentsByType($landlordId),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    private function calculateRevenue($landlordId, $period)
    {
        $query = Payment::whereHas('payable', function($q) use ($landlordId) {
                    $q->whereHas('lease', function($query) use ($landlordId) {
                        $query->where('landlord_id', $landlordId);
                    });
                })
                ->completed();

        switch ($period) {
            case 'today':
                $query->whereDate('completed_at', today());
                break;
            case 'week':
                $query->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereMonth('completed_at', now()->month)
                      ->whereYear('completed_at', now()->year);
                break;
            case 'year':
                $query->whereYear('completed_at', now()->year);
                break;
        }

        return $query->sum('amount');
    }

    private function getPaymentsByMethod($landlordId)
    {
        return Payment::whereHas('payable', function($q) use ($landlordId) {
                    $q->whereHas('lease', function($query) use ($landlordId) {
                        $query->where('landlord_id', $landlordId);
                    });
                })
                ->completed()
                ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('method')
                ->get();
    }

    private function getPaymentsByType($landlordId)
    {
        return Payment::whereHas('payable', function($q) use ($landlordId) {
                    $q->whereHas('lease', function($query) use ($landlordId) {
                        $query->where('landlord_id', $landlordId);
                    });
                })
                ->completed()
                ->selectRaw('type, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('type')
                ->get();
    }

    public function cinetpayWebhook(Request $request)
    {
        try {
            // Vérifier la signature du webhook
            if (!$this->paymentService->verifyCinetpaySignature($request)) {
                return response()->json(['error' => 'Invalid signature'], 403);
            }

            $result = $this->paymentService->handleCinetpayWebhook($request->all());

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Webhook CinetPay error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}