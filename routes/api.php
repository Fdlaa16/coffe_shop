<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\AuthController;
use Modules\Dashboard\Http\Controllers\CustomerController;
use Modules\Dashboard\Http\Controllers\InvoiceController;
use Modules\Dashboard\Http\Controllers\MenuController;
use Modules\Dashboard\Http\Controllers\TableController;
use Modules\Dashboard\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'apiLogin']);
Route::get('menu', [MenuController::class, 'index'])->name('menu.index');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'apiLogout']);
    Route::get('profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('profile-update', [AuthController::class, 'profileUpdate'])->name('profile-update');

    Route::get('customer', [CustomerController::class, 'index'])->name('customer.index');
    Route::get('customer/create', [CustomerController::class, 'create'])->name('customer.create');
    Route::post('customer/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('customer/{id}', [CustomerController::class, 'show'])->name('customer.show');
    Route::get('customer/{id}/edit', [CustomerController::class, 'edit'])->name('customer.edit');
    Route::put('customer/{id}', [CustomerController::class, 'update'])->name('customer.update');
    Route::delete('customer/{id}', [CustomerController::class, 'destroy'])->name('customer.destroy');
    Route::put('customer/{id}/active', [CustomerController::class, 'active'])->name('customer.active');
    Route::put('customer/{id}/approve', [CustomerController::class, 'approve'])->name('customer.approve');
    Route::put('customer/{id}/reject', [CustomerController::class, 'reject'])->name('customer.reject');
    Route::post('customer/export', [CustomerController::class, 'export'])->name('customer.export');

    Route::get('menu/create', [MenuController::class, 'create'])->name('menu.create');
    Route::post('menu/store', [MenuController::class, 'store'])->name('menu.store');
    Route::get('menu/{id}', [MenuController::class, 'show'])->name('menu.show');
    Route::get('menu/{id}/edit', [MenuController::class, 'edit'])->name('menu.edit');
    Route::put('menu/{id}', [MenuController::class, 'update'])->name('menu.update');
    Route::delete('menu/{id}', [MenuController::class, 'destroy'])->name('menu.destroy');
    Route::put('menu/{id}/active', [MenuController::class, 'active'])->name('menu.active');
    Route::put('menu/{id}/approve', [MenuController::class, 'approve'])->name('menu.approve');
    Route::put('menu/{id}/reject', [MenuController::class, 'reject'])->name('menu.reject');

    Route::get('table', [TableController::class, 'index'])->name('table.index');
    Route::get('table/create', [TableController::class, 'create'])->name('table.create');
    Route::post('table/store', [TableController::class, 'store'])->name('table.store');
    Route::get('table/{id}', [TableController::class, 'show'])->name('table.show');
    Route::get('table/{id}/edit', [TableController::class, 'edit'])->name('table.edit');
    Route::put('table/{id}', [TableController::class, 'update'])->name('table.update');
    Route::delete('table/{id}', [TableController::class, 'destroy'])->name('table.destroy');
    Route::put('table/{id}/active', [TableController::class, 'active'])->name('table.active');
    Route::put('table/{id}/approve', [TableController::class, 'approve'])->name('table.approve');
    Route::put('table/{id}/reject', [TableController::class, 'reject'])->name('table.reject');
    Route::get('table/{id}/download-qr', [TableController::class, 'downloadQr'])->name('table.download-qr');

    Route::get('order', [OrderController::class, 'index'])->name('order.index');
    Route::get('order/create', [OrderController::class, 'create'])->name('order.create');
    Route::post('order/store', [OrderController::class, 'store'])->name('order.store');
    Route::get('order/{id}', [OrderController::class, 'show'])->name('order.show');
    Route::get('order/{id}/edit', [OrderController::class, 'edit'])->name('order.edit');
    Route::put('order/{id}', [OrderController::class, 'update'])->name('order.update');
    Route::put('order/{id}/process', [OrderController::class, 'process'])->name('order.process');
    Route::put('order/{id}/finished', [OrderController::class, 'finished'])->name('order.finished');
    Route::put('order/{id}/reject', [OrderController::class, 'reject'])->name('order.reject');
    Route::delete('order/{id}', [OrderController::class, 'destroy'])->name('order.destroy');
    Route::put('order/{id}/active', [OrderController::class, 'active'])->name('order.active');
    Route::put('order/{id}/approve', [OrderController::class, 'approve'])->name('order.approve');
    Route::put('order/{id}/reject', [OrderController::class, 'reject'])->name('order.reject');
    Route::post('order/export', [OrderController::class, 'export'])->name('order.export');

    Route::get('invoice', [InvoiceController::class, 'index'])->name('invoice.index');
    Route::get('invoice/create', [InvoiceController::class, 'create'])->name('invoice.create');
    Route::post('invoice/store', [InvoiceController::class, 'store'])->name('invoice.store');
    Route::get('invoice/{id}', [InvoiceController::class, 'show'])->name('invoice.show');
    Route::get('invoice/{id}/edit', [InvoiceController::class, 'edit'])->name('invoice.edit');
    Route::put('invoice/{id}', [InvoiceController::class, 'update'])->name('invoice.update');
    Route::delete('invoice/{id}', [InvoiceController::class, 'destroy'])->name('invoice.destroy');
    Route::put('invoice/{id}/active', [InvoiceController::class, 'active'])->name('invoice.active');
    Route::put('invoice/{id}/approve', [InvoiceController::class, 'approve'])->name('invoice.approve');
    Route::put('invoice/{id}/reject', [InvoiceController::class, 'reject'])->name('invoice.reject');
    Route::get('invoice/{id}/download-receipt', [InvoiceController::class, 'downloadReceipt'])->name('invoice.download-receipt');
    Route::post('invoice/export', [InvoiceController::class, 'export'])->name('invoice.export');
});

Route::get('payment/methods', [PaymentController::class, 'getPaymentMethods']);
Route::post('payment/create', [PaymentController::class, 'createPayment']);
Route::post('payment/duitku/callback', [PaymentController::class, 'callback']);
