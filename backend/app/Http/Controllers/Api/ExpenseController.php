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
// CONTROLLER: ExpenseController
// ============================================
class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::where('user_id', $request->user()->id);

        if ($request->has('category')) {
            $query->inCategory($request->category);
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->inDateRange($request->start_date, $request->end_date);
        }

        $expenses = $query->orderBy('expense_date', 'desc')
                         ->paginate($request->get('per_page', 20));

        return ExpenseResource::collection($expenses);
    }

    public function store(CreateExpenseRequest $request)
    {
        $expense = Expense::create([
            'client_id' => $request->user()->getKey(),
            'category' => $request->category,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date ?? now(),
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Dépense ajoutée avec succès',
            'data' => new ExpenseResource($expense),
        ], 201);
    }

    public function getByCategory(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $expenses = Expense::where('user_id', Auth::id())
                          ->inDateRange($startDate, $endDate)
                          ->selectRaw('category, SUM(amount) as total')
                          ->groupBy('category')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ]);
    }

    public function getByMonth(Request $request)
    {
        $year = $request->get('year', now()->year);

        $expenses = Expense::where('user_id', Auth::id())
                          ->whereYear('expense_date', $year)
                          ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total')
                          ->groupBy('month')
                          ->orderBy('month')
                          ->get();

        return response()->json([
            'success' => true,
            'data' => $expenses,
        ]);
    }

    public function getSummary(Request $request)
    {
        $userId = Auth::id();
        $currentMonth = now();

        $data = [
            'current_month' => Expense::where('user_id', $userId)
                                     ->inMonth($currentMonth->year, $currentMonth->month)
                                     ->sum('amount'),
            'last_month' => Expense::where('user_id', $userId)
                                  ->inMonth($currentMonth->copy()->subMonth()->year, $currentMonth->copy()->subMonth()->month)
                                  ->sum('amount'),
            'total_all_time' => Expense::where('user_id', $userId)->sum('amount'),
            'by_category' => Expense::where('user_id', $userId)
                                   ->inMonth($currentMonth->year, $currentMonth->month)
                                   ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                                   ->groupBy('category')
                                   ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}