<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\InvoiceController;
use Modules\Order\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('order')->group(function () {
    Route::post('create', [OrderController::class, 'store']);

    Route::get('payment/methods', [PaymentController::class, 'getPaymentMethods']);
    Route::post('payment/create', [PaymentController::class, 'createPayment']);
    Route::post('payment/duitku/callback', [PaymentController::class, 'callback']);
    Route::post('payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');

    Route::get('invoice/{id}/download-receipt', [InvoiceController::class, 'downloadReceipt'])->name('invoice.download-receipt');
});
