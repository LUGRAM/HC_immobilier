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
// CONTROLLER: NotificationController
// ============================================
class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
                                ->notifications()
                                ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notifications,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => ['count' => $count],
        ]);
    }

    public function markAsRead($id, Request $request)
    {
        $notification = $request->user()
                               ->notifications()
                               ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
        ]);
    }

    public function markAllAsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications marquées comme lues',
        ]);
    }

    public function registerDevice(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'required|in:android,ios,web',
            'device_name' => 'nullable|string',
        ]);

        $deviceToken = DeviceToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->token,
            ],
            [
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Appareil enregistré avec succès',
            'data' => $deviceToken,
        ]);
    }

    public function unregisterDevice($token, Request $request)
    {
        DeviceToken::where('user_id', $request->user()->id)
                  ->where('token', $token)
                  ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Appareil désenregistré avec succès',
        ]);
    }
}