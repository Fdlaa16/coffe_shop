<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\CustomerController;
use Modules\Dashboard\Http\Controllers\DrinkController;
use Modules\Dashboard\Http\Controllers\FoodController;
use Modules\Dashboard\Http\Controllers\TableController;
use Modules\Order\Http\Controllers\OrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

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

Route::get('food', [FoodController::class, 'index'])->name('food.index');
Route::get('food/create', [FoodController::class, 'create'])->name('food.create');
Route::post('food/store', [FoodController::class, 'store'])->name('food.store');
Route::get('food/{id}', [FoodController::class, 'show'])->name('food.show');
Route::get('food/{id}/edit', [FoodController::class, 'edit'])->name('food.edit');
Route::put('food/{id}', [FoodController::class, 'update'])->name('food.update');
Route::delete('food/{id}', [FoodController::class, 'destroy'])->name('food.destroy');
Route::put('food/{id}/active', [FoodController::class, 'active'])->name('food.active');
Route::put('food/{id}/approve', [FoodController::class, 'approve'])->name('food.approve');
Route::put('food/{id}/reject', [FoodController::class, 'reject'])->name('food.reject');

Route::get('drink', [DrinkController::class, 'index'])->name('drink.index');
Route::get('drink/create', [DrinkController::class, 'create'])->name('drink.create');
Route::post('drink/store', [DrinkController::class, 'store'])->name('drink.store');
Route::get('drink/{id}', [DrinkController::class, 'show'])->name('drink.show');
Route::get('drink/{id}/edit', [DrinkController::class, 'edit'])->name('drink.edit');
Route::put('drink/{id}', [DrinkController::class, 'update'])->name('drink.update');
Route::delete('drink/{id}', [DrinkController::class, 'destroy'])->name('drink.destroy');
Route::put('drink/{id}/active', [DrinkController::class, 'active'])->name('drink.active');
Route::put('drink/{id}/approve', [DrinkController::class, 'approve'])->name('drink.approve');
Route::put('drink/{id}/reject', [DrinkController::class, 'reject'])->name('drink.reject');

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

Route::get('order', [OrderController::class, 'index'])->name('order.index');
Route::get('order/create', [OrderController::class, 'create'])->name('order.create');
Route::post('order/store', [OrderController::class, 'store'])->name('order.store');
Route::get('order/{id}', [OrderController::class, 'show'])->name('order.show');
Route::get('order/{id}/edit', [OrderController::class, 'edit'])->name('order.edit');
Route::put('order/{id}', [OrderController::class, 'update'])->name('order.update');
Route::delete('order/{id}', [OrderController::class, 'destroy'])->name('order.destroy');
Route::put('order/{id}/active', [OrderController::class, 'active'])->name('order.active');
Route::put('order/{id}/approve', [OrderController::class, 'approve'])->name('order.approve');
Route::put('order/{id}/reject', [OrderController::class, 'reject'])->name('order.reject');
