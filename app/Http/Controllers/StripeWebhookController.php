<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use App\Models\User;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        Log::info('Stripe webhook recibido', [
            'stripe_signature_present' => (bool) $request->header('Stripe-Signature'),
            'content_type' => $request->header('Content-Type'),
            'payload_len' => strlen($request->getContent()),
        ]);

        $endpoint_secret = config('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        $payload    = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        if (!$sig_header) {
            Log::error('Stripe webhook: falta Stripe-Signature header');
            return response('Missing Stripe-Signature', Response::HTTP_BAD_REQUEST);
        }

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload inválido', ['error' => $e->getMessage()]);
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            Log::error('Firma de Stripe no válida', ['error' => $e->getMessage()]);
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        Log::info('Stripe event', ['event_id' => $event->id, 'type' => $event->type]);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                if (($session->payment_status ?? null) !== 'paid') {
                    Log::warning('checkout.session.completed pero no paid', [
                        'session_id' => $session->id,
                        'payment_status' => $session->payment_status ?? null,
                    ]);
                    break;
                }

                $userId  = $session->metadata->user_id ?? null;
                $purpose = $session->metadata->purpose ?? 'deposit';

                if (!$userId) {
                    Log::warning('Stripe webhook: metadata.user_id ausente', ['session_id' => $session->id]);
                    break;
                }

                $user = User::find($userId);

                if (!$user) {
                    Log::warning('Stripe webhook: usuario no encontrado', ['user_id' => $userId]);
                    break;
                }

                if ($purpose === 'final') {
                    $user->finalPayment = 1;
                } elseif ($purpose === 'session') {
                    // futuro
                } else {
                    $user->depositStatus = 1;
                }

                $user->save();

                Log::info('Stripe webhook: actualizado usuario', [
                    'user_id' => $userId,
                    'purpose' => $purpose,
                ]);
                break;

            case 'payment_intent.payment_failed':
                Log::warning('Stripe webhook: pago fallido', [
                    'id' => $event->data->object->id ?? null,
                ]);
                break;

            case 'charge.refunded':
                Log::info('Stripe webhook: pago reembolsado', [
                    'id' => $event->data->object->id ?? null,
                ]);
                break;

            default:
                Log::info('Stripe webhook: evento no manejado', ['type' => $event->type]);
        }

        return response('Webhook recibido', Response::HTTP_OK);
    }
}
