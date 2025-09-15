<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::get('{any}', function () {
    return view('application');
})->where('any', '^(?!api|storage).*$');

Route::get('/payment/success', [PaymentController::class, 'paymentSuccess'])->name('payment.success');

Route::get('login')->name('login');
