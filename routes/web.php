<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CarpetLabelController;
use App\Http\Controllers\Admin\InvoicePDFController;
use App\Http\Controllers\Admin\BulkCarpetQRController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

// Admin routes for carpet label printing and invoice generation
Route::middleware([
    'auth',
])->prefix('admin')->name('admin.')->group(function () {
    // Carpet label printing routes
    Route::get('/carpets/{carpet}/print-label', [CarpetLabelController::class, 'printLabel'])
        ->name('carpets.print-label');
    Route::get('/orders/{order}/print-carpet-labels', [CarpetLabelController::class, 'printOrderLabels'])
        ->name('orders.print-carpet-labels');
        
    // Carpet QR code generation route
    Route::get('/carpets/{carpet}/qr-code', [\App\Http\Controllers\Admin\CarpetQRController::class, 'showQR'])
        ->name('carpets.qr-code');
        
    // Bulk carpet QR code generation route
    Route::get('/carpets/bulk-qr-codes', [BulkCarpetQRController::class, 'generateBulkQrCodes'])
        ->name('carpets.bulk-qr-codes');
    
    // Invoice PDF generation route
    Route::get('/invoices/{invoice}/pdf', [InvoicePDFController::class, 'generatePDF'])
        ->name('invoices.pdf');
});

// Payment routes (publicly accessible)
Route::get('/pay/{invoice}', [PaymentController::class, 'showPaymentPage'])->name('payments.checkout');
Route::get('/pay/{invoice}/success', [PaymentController::class, 'success'])->name('payments.success');

// Payment webhook (publicly accessible, no CSRF protection)
Route::post('/api/payments/webhook', [PaymentController::class, 'webhook'])
    ->middleware('api')
    ->name('payments.webhook');
