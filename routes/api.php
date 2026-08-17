<?php

use App\Http\Controllers\MobileMoneyWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/mobile-money/webhook', [MobileMoneyWebhookController::class, 'handle'])
    ->name('mobile-money.webhook');
