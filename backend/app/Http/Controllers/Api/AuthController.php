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
// CONTROLLER: AuthController
// ============================================
class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? 'client',
            ]);

            // Générer OTP pour vérification
            $otp = rand(100000, 999999);
            Cache::put("otp:{$user->phone}", $otp, now()->addMinutes(10));

            // Envoyer OTP (SMS ou Email)
            // NotificationService::sendOtp($user, $otp);

            $token = $user->createToken('auth-token')->plainTextToken;

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Compte créé avec succès. Veuillez vérifier votre téléphone.',
                'data' => [
                    'user' => new UserResource($user),
                    'token' => $token,
                ],
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du compte',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)
                    ->orWhere('phone', $request->email)
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // Mettre à jour dernière connexion
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user()->load([
                'leases.property',
                'appointments.property'
            ])),
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|digits:6',
        ]);

        $cachedOtp = Cache::get("otp:{$request->phone}");

        if (!$cachedOtp || $cachedOtp != $request->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Code OTP invalide ou expiré',
            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        $user->update(['phone_verified_at' => now()]);

        Cache::forget("otp:{$request->phone}");

        return response()->json([
            'success' => true,
            'message' => 'Téléphone vérifié avec succès',
        ]);
    }
}