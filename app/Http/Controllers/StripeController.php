<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Config;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

class StripeController extends Controller
{
    // Ya puedes mantener redirectToCheckout() si lo usas en otra parte

    public function createCheckout(Request $request)
    {
        $validated = $request->validate([
            'currency' => 'nullable|string|size:3',
            'purpose'  => 'nullable|string|max:50', // 'deposit' | 'final' | 'session'
        ]);

        $user     = $request->user();               // requiere jwt.auth
        $userId   = $user?->id;
        $email    = $user?->email;
        $purpose  = $validated['purpose'] ?? 'deposit';
        $currency = strtolower($validated['currency'] ?? 'eur');

        // Precio desde BBDD (tabla configs)
        $cfg = Config::first();
        abort_if(!$cfg, 500, 'No hay configuración de precios.');
        $amount = match ($purpose) {
            'final'   => (int) round($cfg->price_course  * 100),
            'session' => (int) round($cfg->price_session * 100),
            default   => (int) round($cfg->price_booking * 100),
        };

        Stripe::setApiKey(config('services.stripe.secret', env('STRIPE_SECRET')));

        $successUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/')
            . '/campus/inactive/payment/success?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/')
            . '/campus/inactive/payment/cancel';

        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $email,
            'client_reference_id' => (string) $userId,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => $amount,
                    'product_data' => [
                        'name' => $purpose === 'final' ? 'Pago final REBIRTH' : 'Reserva REBIRTH',
                    ],
                ],
            ]],
            'metadata' => [
                'user_id'      => (string) $userId,
                'purpose'      => $purpose,
                'amount_cents' => (string) $amount,
            ],
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
        ]);

        Log::info('Stripe session creada', ['session_id' => $session->id, 'user_id' => $userId, 'purpose' => $purpose, 'amount' => $amount]);

        return response()->json(['id' => $session->id, 'url' => $session->url]);
    }
}
