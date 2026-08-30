<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessSettingsController;
use App\Http\Controllers\CashierModeController;
use App\Http\Controllers\ContactImportController;
use App\Http\Controllers\CustomerDebtController;
use App\Http\Controllers\DamageController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SalesReportController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\EntityController as SuperAdminEntityController;
use App\Http\Controllers\SuperAdmin\GlobalSearchController as SuperAdminGlobalSearchController;
use App\Http\Controllers\SuperAdmin\ImpersonationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;
use App\Models\Business;
use Illuminate\Support\Facades\Route;

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout.get');

Route::middleware(['maintenance'])->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [AuthController::class, 'showRegister'])->name('home');
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/portal', [AuthController::class, 'showPortal'])->name('portal');
        Route::get('/business/{portal}/login', [AuthController::class, 'showBusinessLogin'])->name('business.login');
        Route::post('/business/{portal}/login', [AuthController::class, 'businessLogin']);
        Route::get('/superadmin/login', [AuthController::class, 'showSuperAdminLogin'])->name('superadmin.login');
        Route::post('/superadmin/login', [AuthController::class, 'superAdminLogin']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::get('/register/check-username', [AuthController::class, 'checkUsername'])->name('register.check-username');
        Route::post('/register', [AuthController::class, 'register']);
    });

    Route::middleware(['auth'])->group(function () {
        Route::post('/leave-impersonation', [ImpersonationController::class, 'leave'])->name('impersonation.leave');

        Route::get('/dashboard', function () {
            $user = auth()->user();
            if ($user->isPlatformAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }
            abort_unless($user->business, 403);
            if ($user->isCashier()) {
                return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
            }
            if ($user->can('view-dashboard')) {
                return redirect()->route('tenant.dashboard', ['business' => $user->business->slug]);
            }
            return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
        });
        Route::get('/pos', function () {
            $user = auth()->user();
            abort_unless($user->business, 403);
            abort_unless($user->can('access-pos'), 403);
            return redirect()->route('tenant.pos.index', ['business' => $user->business->slug]);
        });
        Route::get('/inventory', function () {
            $user = auth()->user();
            abort_unless($user->business, 403);
            abort_unless($user->can('view-inventory'), 403);
            return redirect()->route('tenant.inventory.index', ['business' => $user->business->slug]);
        });

        Route::get('/subscription/payment', [SubscriptionController::class, 'payment'])->name('subscription.payment');
        Route::post('/subscription/initiate', [SubscriptionController::class, 'initiate'])->middleware('can:manage-billing')->name('subscription.initiate');
        Route::get('/subscription/simulate/{reference}', [SubscriptionController::class, 'simulate'])->name('subscription.simulate');
        Route::post('/subscription/simulate/{reference}/complete', [SubscriptionController::class, 'simulateComplete'])->name('subscription.simulate.complete');
    });

    Route::prefix('app/{business}')
        ->middleware(['auth', 'tenant.access', 'cashier.isolation'])
        ->name('tenant.')
        ->group(function () {
            Route::middleware(['subscription.active'])->group(function () {
                Route::middleware(['can:view-dashboard', 'management.access'])->group(function () {
                    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
                    Route::get('/dashboard/analytics', [DashboardController::class, 'analytics'])->name('dashboard.analytics');

                    Route::middleware(['can:log-damages'])->prefix('damages')->name('damages.')->group(function () {
                        Route::get('/', [DamageController::class, 'index'])->name('index');
                        Route::post('/', [DamageController::class, 'store'])->name('store');
                    });

                    Route::middleware(['can:view-sales-reports'])->prefix('reports/sales')->name('reports.sales.')->group(function () {
                        Route::get('/', [SalesReportController::class, 'index'])->name('index');
                        Route::get('/print', [SalesReportController::class, 'print'])->name('print');
                    });

                    Route::middleware(['can:view-expenses', 'management.access'])->prefix('expenses')->name('expenses.')->group(function () {
                        Route::get('/', [ExpenseController::class, 'index'])->name('index');
                        Route::get('/{expense}/edit', [ExpenseController::class, 'edit'])->name('edit');
                        Route::put('/{expense}', [ExpenseController::class, 'update'])->name('update');
                        Route::delete('/{expense}', [ExpenseController::class, 'destroy'])->name('destroy');
                    });

                    Route::middleware(['can:manage-employees'])->prefix('staff')->name('staff.')->group(function () {
                        Route::get('/', [EmployeeController::class, 'index'])->name('index');
                        Route::get('/create', [EmployeeController::class, 'create'])->name('create');
                        Route::post('/', [EmployeeController::class, 'store'])->name('store');
                        Route::get('/{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
                        Route::put('/{employee}', [EmployeeController::class, 'update'])->name('update');
                        Route::delete('/{employee}', [EmployeeController::class, 'destroy'])->name('destroy');
                    });

                    Route::middleware(['can:manage-settings'])->group(function () {
                        Route::get('/business', [BusinessSettingsController::class, 'edit'])->name('business.edit');
                        Route::put('/business', [BusinessSettingsController::class, 'update'])->name('business.update');
                    });
                });

                Route::middleware(['can:view-customers', 'management.access'])->prefix('contacts')->name('contacts.')->group(function () {
                    Route::get('/', [CustomerDebtController::class, 'index'])->name('index');
                    Route::get('/import', [ContactImportController::class, 'show'])->middleware('can:manage-debts')->name('import.show');
                    Route::post('/import', [ContactImportController::class, 'upload'])->middleware('can:manage-debts')->name('import.upload');
                    Route::get('/import/map', [ContactImportController::class, 'map'])->middleware('can:manage-debts')->name('import.map');
                    Route::post('/import/process', [ContactImportController::class, 'process'])->middleware('can:manage-debts')->name('import.process');
                    Route::get('/create', [CustomerDebtController::class, 'create'])->middleware('can:manage-debts')->name('create');
                    Route::post('/', [CustomerDebtController::class, 'store'])->middleware('can:manage-debts')->name('store');
                    Route::get('/{customer}', [CustomerDebtController::class, 'show'])->name('show');
                    Route::get('/{customer}/edit', [CustomerDebtController::class, 'edit'])->middleware('can:manage-debts')->name('edit');
                    Route::put('/{customer}', [CustomerDebtController::class, 'update'])->middleware('can:manage-debts')->name('update');
                    Route::delete('/{customer}', [CustomerDebtController::class, 'destroy'])->middleware('can:manage-debts')->name('destroy');
                    Route::post('/{customer}/payment', [CustomerDebtController::class, 'recordPayment'])->middleware('can:manage-debts')->name('payment');
                });

                Route::middleware(['can:view-inventory', 'management.access'])->prefix('inventory')->name('inventory.')->group(function () {
                    Route::get('/', [InventoryController::class, 'index'])->name('index');
                    Route::middleware(['can:create-inventory'])->group(function () {
                        Route::get('/create', [InventoryController::class, 'create'])->name('create');
                        Route::post('/', [InventoryController::class, 'store'])->name('store');
                    });
                    Route::middleware(['can:update-inventory'])->group(function () {
                        Route::get('/{product}/edit', [InventoryController::class, 'edit'])->name('edit');
                        Route::put('/{product}', [InventoryController::class, 'update'])->name('update');
                    });
                    Route::delete('/{product}', [InventoryController::class, 'destroy'])
                        ->middleware('can:delete-inventory')
                        ->name('destroy');
                });

                Route::middleware(['can:manage-profile', 'management.access'])->group(function () {
                    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
                    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
                });

                Route::middleware(['can:record-expenses'])->group(function () {
                    Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
                    Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
                });

                Route::middleware(['can:switch-cashier-mode'])->prefix('cashier-mode')->name('cashier-mode.')->group(function () {
                    Route::post('/enable', [CashierModeController::class, 'enable'])->name('enable');
                    Route::post('/disable', [CashierModeController::class, 'disable'])->name('disable');
                });

                Route::middleware(['can:access-pos'])->prefix('pos')->name('pos.')->group(function () {
                    Route::get('/', [PosController::class, 'index'])->name('index');
                    Route::get('/search', [PosController::class, 'search'])->name('search');
                    Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
                });

                Route::prefix('reconciliation')->name('reconciliation.')->group(function () {
                    Route::get('/', [ReconciliationController::class, 'index'])
                        ->middleware(['can:view-reconciliation-history', 'management.access'])
                        ->name('index');

                    Route::middleware(['can:submit-reconciliation'])->group(function () {
                        Route::get('/create', [ReconciliationController::class, 'create'])->name('create');
                        Route::post('/', [ReconciliationController::class, 'store'])->name('store');
                    });

                    Route::middleware(['can:view-reconciliation-history'])->group(function () {
                        Route::get('/{reconciliation}/print', [ReconciliationController::class, 'print'])->name('print');
                        Route::get('/{reconciliation}', [ReconciliationController::class, 'show'])->name('show');
                    });
                });
            });
        });
});

Route::prefix('superadmin')
    ->middleware(['auth', 'superadmin'])
    ->name('superadmin.')
    ->group(function () {
        Route::get('/', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/search', [SuperAdminGlobalSearchController::class, 'index'])->name('search');
        Route::get('/activity', [ActivityLogController::class, 'index'])->name('activity');

        Route::prefix('entities/{entity}')->where(['entity' => 'businesses|users|products|customers|sales|expenses'])->name('entities.')->group(function () {
            Route::get('/', [SuperAdminEntityController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminEntityController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminEntityController::class, 'store'])->name('store');
            Route::get('/{record}', [SuperAdminEntityController::class, 'show'])->whereNumber('record')->name('show');
            Route::middleware('platform.full')->group(function () {
                Route::get('/{record}/edit', [SuperAdminEntityController::class, 'edit'])->whereNumber('record')->name('edit');
                Route::put('/{record}', [SuperAdminEntityController::class, 'update'])->whereNumber('record')->name('update');
                Route::delete('/{record}', [SuperAdminEntityController::class, 'destroy'])->whereNumber('record')->name('destroy');
            });
        });

        Route::middleware('platform.full')->group(function () {
            Route::post('/businesses/{businessId}/impersonate', [ImpersonationController::class, 'start'])->whereNumber('businessId')->name('impersonate.start');
            Route::get('/settings', [SuperAdminSettingsController::class, 'edit'])->name('settings');
            Route::put('/settings', [SuperAdminSettingsController::class, 'update'])->name('settings.update');
        });
    });
