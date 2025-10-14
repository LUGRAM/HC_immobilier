<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    PropertyController,
    LeaseController,
    PaymentController,
    InvoiceController,
    AppointmentController,
    MaintenanceRequestController,
    DashboardController
};

/*
|--------------------------------------------------------------------------
| Routes Publiques
|--------------------------------------------------------------------------
*/

// Authentification
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Propriétés publiques (consultation)
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('/properties/{id}', [PropertyController::class, 'show']);
Route::get('/properties/{id}/similar', [PropertyController::class, 'similar']);

/*
|--------------------------------------------------------------------------
| Routes Protégées (Authentification requise)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    
    // ========================================
    // AUTH & PROFILE
    // ========================================
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/user/update', [AuthController::class, 'updateProfile']);
    Route::post('/user/change-password', [AuthController::class, 'changePassword']);
    Route::post('/user/upload-avatar', [AuthController::class, 'uploadAvatar']);
    
    // ========================================
    // DASHBOARD
    // ========================================
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    
    // ========================================
    // APPOINTMENTS (Rendez-vous)
    // ========================================
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
    Route::post('/appointments/{id}/complete', [AppointmentController::class, 'complete']);
    
    // ========================================
    // LEASES (Baux)
    // ========================================
    Route::get('/leases', [LeaseController::class, 'index']);
    Route::get('/leases/active', [LeaseController::class, 'getActiveLease']);
    Route::post('/leases/request/{appointmentId}', [LeaseController::class, 'requestFromAppointment']);
    Route::get('/leases/{id}', [LeaseController::class, 'show']);
    
    // ========================================
    // INVOICES (Factures) - Locataires
    // ========================================
    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
    Route::post('/invoices/{id}/pay', [InvoiceController::class, 'initiatePayment']);
    Route::get('/invoices/{id}/receipt', [InvoiceController::class, 'getReceipt']);
    
    // ========================================
    // PAYMENTS (Paiements)
    // ========================================
    Route::get('/payments', [PaymentController::class, 'history']);
    Route::get('/payments/{transactionId}', [PaymentController::class, 'show']);
    
    // ========================================
    // MAINTENANCE (Demandes de maintenance)
    // ========================================
    Route::get('/maintenance', [MaintenanceRequestController::class, 'index']);
    Route::post('/maintenance', [MaintenanceRequestController::class, 'store']);
    Route::get('/maintenance/{id}', [MaintenanceRequestController::class, 'show']);
    Route::post('/maintenance/{id}/cancel', [MaintenanceRequestController::class, 'cancel']);
    Route::get('/maintenance/stats', [MaintenanceRequestController::class, 'stats']);
    
    // ========================================
    // ROUTES LANDLORD (Bailleurs)
    // ========================================
    Route::prefix('landlord')->group(function () {
        
        // Propriétés
        Route::get('/properties', [PropertyController::class, 'landlordIndex']);
        Route::post('/properties', [PropertyController::class, 'store']);
        Route::get('/properties/{id}', [PropertyController::class, 'show']);
        Route::put('/properties/{id}', [PropertyController::class, 'update']);
        Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
        Route::post('/properties/{id}/feature', [PropertyController::class, 'toggleFeatured']);
        
        // Baux
        Route::get('/leases', [LeaseController::class, 'landlordIndex']);
        Route::post('/leases/{id}/approve', [LeaseController::class, 'approve']);
        Route::post('/leases/{id}/reject', [LeaseController::class, 'reject']);
        Route::post('/leases/{id}/terminate', [LeaseController::class, 'terminate']);
        
        // Locataires
        Route::get('/tenants', [LeaseController::class, 'myTenants']);
        Route::get('/tenants/{id}', [LeaseController::class, 'tenantDetails']);
        
        // Factures
        Route::get('/invoices', [InvoiceController::class, 'landlordInvoices']);
        Route::post('/invoices', [InvoiceController::class, 'createForTenant']);
        Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
        Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
        
        // Paiements reçus
        Route::get('/payments', [PaymentController::class, 'receivedPayments']);
        Route::get('/payments/summary', [PaymentController::class, 'paymentsSummary']);
        
        // Rendez-vous
        Route::get('/appointments', [AppointmentController::class, 'landlordAppointments']);
        Route::post('/appointments/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/appointments/{id}/reject', [AppointmentController::class, 'reject']);
        
        // Maintenance
        Route::get('/maintenance', [MaintenanceRequestController::class, 'landlordIndex']);
        Route::put('/maintenance/{id}', [MaintenanceRequestController::class, 'update']);
        Route::post('/maintenance/{id}/assign', [MaintenanceRequestController::class, 'assign']);
        
        // Statistiques
        Route::get('/stats', [DashboardController::class, 'landlordStats']);
    });
});

/*
|--------------------------------------------------------------------------
| Webhooks (Sans authentification)
|--------------------------------------------------------------------------
*/

Route::post('/webhook/cinetpay', [PaymentController::class, 'cinetpayWebhook']);
Route::post('/webhook/payment', [PaymentController::class, 'handleWebhook']);



Route::get('/ping', function () {
    return response()->json(['success' => true, 'message' => 'API opérationnelle']);
});
