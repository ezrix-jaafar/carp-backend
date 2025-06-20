<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/register', [App\Http\Controllers\Api\AuthController::class, 'register']);

// ToyyibPay webhook (no authentication)
Route::post('/payments/webhook', [App\Http\Controllers\Api\ToyyibPayWebhookController::class, 'handleWebhook']);

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // User information
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    
    // Orders
    Route::apiResource('orders', App\Http\Controllers\Api\OrderController::class);
    Route::put('/orders/{id}/assign', [App\Http\Controllers\Api\OrderController::class, 'assignAgent']);
    Route::put('/orders/{id}/status', [App\Http\Controllers\Api\OrderController::class, 'updateStatus']);
    
    // Carpets
    Route::get('/carpets', [App\Http\Controllers\Api\CarpetController::class, 'all']);
    Route::get('/carpets/assigned', [App\Http\Controllers\Api\CarpetController::class, 'assigned']);
    Route::apiResource('carpets', App\Http\Controllers\Api\CarpetController::class)->except(['index']);
    Route::post('/carpets/{id}/images', [App\Http\Controllers\Api\CarpetController::class, 'uploadImages']);
    Route::delete('/carpets/{id}/images/{imageId}', [App\Http\Controllers\Api\CarpetController::class, 'deleteImage']);
    
    // Bulk Carpet Generation and QR Code Functionality
    Route::post('/orders/{order}/bulk-carpets', [App\Http\Controllers\Api\BulkCarpetController::class, 'generateBulkCarpets']);
    Route::get('/carpets/qr/{qrCode}', [App\Http\Controllers\Api\BulkCarpetController::class, 'getCarpetByQrCode']);
    Route::put('/carpets/qr/{qrCode}', [App\Http\Controllers\Api\BulkCarpetController::class, 'updateCarpetByQrCode']);
    
    // Invoices
    Route::apiResource('invoices', App\Http\Controllers\Api\InvoiceController::class);
    Route::get('/orders/{orderId}/calculate-total', [App\Http\Controllers\Api\InvoiceController::class, 'calculateTotal']);
    
    // Payments
    Route::apiResource('payments', App\Http\Controllers\Api\PaymentController::class);
    Route::post('/payments/create', [App\Http\Controllers\Api\PaymentController::class, 'createPayment']);
    Route::get('/payments/{billCode}/status', [App\Http\Controllers\Api\PaymentController::class, 'checkPaymentStatus']);
    Route::get('/invoices/{invoiceId}/payments', [App\Http\Controllers\Api\PaymentController::class, 'getInvoicePayments']);
    Route::post('/payments/check-status', [App\Http\Controllers\Api\ToyyibPayWebhookController::class, 'checkAndUpdateStatus']);
    
    // Commissions
    Route::apiResource('commissions', App\Http\Controllers\Api\CommissionController::class);
    Route::get('/agents/{agentId}/commissions', [App\Http\Controllers\Api\CommissionController::class, 'getAgentCommissions']);
    Route::post('/commissions/calculate', [App\Http\Controllers\Api\CommissionController::class, 'calculateCommission']);
    
        // Devices for push notifications
    Route::post('/devices', [App\Http\Controllers\Api\DeviceController::class, 'store']);
    Route::delete('/devices/{token}', [App\Http\Controllers\Api\DeviceController::class, 'destroy']);

    // Agents
    Route::apiResource('agents', App\Http\Controllers\Api\AgentController::class);
    Route::put('/agents/{id}/toggle-status', [App\Http\Controllers\Api\AgentController::class, 'toggleStatus']);
    Route::get('/agents/{id}/commission-summary', [App\Http\Controllers\Api\AgentController::class, 'commissionSummary']);
    
    // Clients
    Route::apiResource('clients', App\Http\Controllers\Api\ClientController::class);
    Route::get('/clients/{id}/orders', [App\Http\Controllers\Api\ClientController::class, 'orderHistory']);
    Route::get('/clients/{id}/payments', [App\Http\Controllers\Api\ClientController::class, 'paymentHistory']);
    
    // Addresses
    Route::apiResource('addresses', App\Http\Controllers\API\AddressController::class);
    Route::put('/addresses/{id}/set-default', [App\Http\Controllers\API\AddressController::class, 'setDefault']);
    
    // Carpet Types
    Route::apiResource('carpet-types', App\Http\Controllers\Api\CarpetTypeController::class);
    
    // Addon Services
    Route::apiResource('addon-services', App\Http\Controllers\Api\AddonServiceController::class);
    Route::post('/carpets/{carpetId}/addon-services', [App\Http\Controllers\Api\CarpetController::class, 'assignAddonService']);
    
    // Commission Types
    Route::apiResource('commission-types', App\Http\Controllers\Api\CommissionTypeController::class);
    Route::post('/agents/{agentId}/commission-types', [App\Http\Controllers\Api\AgentController::class, 'assignCommissionType']);
});
