<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerDebtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/subscription/payment', [SubscriptionController::class, 'payment'])->name('subscription.payment');
    Route::post('/subscription/initiate', [SubscriptionController::class, 'initiate'])->middleware('can:manage-billing')->name('subscription.initiate');
    Route::get('/subscription/simulate/{reference}', [SubscriptionController::class, 'simulate'])->name('subscription.simulate');
    Route::post('/subscription/simulate/{reference}/complete', [SubscriptionController::class, 'simulateComplete'])->name('subscription.simulate.complete');
});

Route::prefix('app/{business}')
    ->middleware(['auth', 'tenant.access'])
    ->name('tenant.')
    ->group(function () {
        Route::middleware(['subscription.active'])->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->middleware('can:view-dashboard')
                ->name('dashboard');

            Route::middleware(['can:manage-inventory'])->prefix('inventory')->name('inventory.')->group(function () {
                Route::get('/', [InventoryController::class, 'index'])->name('index');
                Route::get('/create', [InventoryController::class, 'create'])->name('create');
                Route::post('/', [InventoryController::class, 'store'])->name('store');
                Route::get('/{product}/edit', [InventoryController::class, 'edit'])->name('edit');
                Route::put('/{product}', [InventoryController::class, 'update'])->name('update');
                Route::delete('/{product}', [InventoryController::class, 'destroy'])->name('destroy');
            });

            Route::middleware(['can:access-pos'])->prefix('pos')->name('pos.')->group(function () {
                Route::get('/', [PosController::class, 'index'])->name('index');
                Route::get('/search', [PosController::class, 'search'])->name('search');
                Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
            });

            Route::prefix('reconciliation')->name('reconciliation.')->group(function () {
                Route::get('/', [ReconciliationController::class, 'index'])->name('index');
                Route::get('/create', [ReconciliationController::class, 'create'])
                    ->middleware('can:submit-reconciliation')
                    ->name('create');
                Route::post('/', [ReconciliationController::class, 'store'])
                    ->middleware('can:submit-reconciliation')
                    ->name('store');
            });

            Route::middleware(['can:manage-debts'])->prefix('debts')->name('debts.')->group(function () {
                Route::get('/', [CustomerDebtController::class, 'index'])->name('index');
                Route::get('/create', [CustomerDebtController::class, 'create'])->name('create');
                Route::post('/', [CustomerDebtController::class, 'store'])->name('store');
                Route::get('/{customer}', [CustomerDebtController::class, 'show'])->name('show');
                Route::post('/{customer}/payment', [CustomerDebtController::class, 'recordPayment'])->name('payment');
            });
        });
    });
