<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;



Route::post('api.webhook', [StripeWebhookController::class, 'handle'])
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('stripe.webhook');
    
Route::get('/', function () {
    return view('welcome');
});
