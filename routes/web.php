<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('api.webhook', [StripeWebhookController::class, 'handle'])
     ->name('stripe.webhook');

Route::get('/', function () {
    return view('welcome');
});
