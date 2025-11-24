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
        // Clave secreta del webhook (config/services.php)
        $endpoint_secret = config('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        $payload    = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event      = null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload inválido', ['error' => $e->getMessage()]);
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (SignatureVerificationException $e) {
            Log::error('Firma de Stripe no válida', ['error' => $e->getMessage()]);
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        // Procesar tipos de evento
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object; // \Stripe\Checkout\Session

                $userId  = $session->metadata->user_id ?? null;
                $purpose = $session->metadata->purpose ?? 'deposit';

                if ($userId) {
                    $user = User::find($userId);

                    if ($user) {
                        if ($purpose === 'final') {
                            $user->finalPayment  = 1;
                        } elseif ($purpose === 'session') {
                            // Si en el futuro quieres hacer algo especial con "session"
                        } else {
                            // deposit (reserva)
                            $user->depositStatus = 1;
                        }

                        $user->save();

                        Log::info('Stripe webhook: actualizado usuario', [
                            'user_id' => $userId,
                            'purpose' => $purpose,
                        ]);
                    } else {
                        Log::warning('Stripe webhook: usuario no encontrado', [
                            'user_id' => $userId,
                        ]);
                    }
                } else {
                    Log::warning('Stripe webhook: metadata.user_id ausente', [
                        'session_id' => $session->id,
                    ]);
                }
                break;

            case 'payment_intent.payment_failed':
                Log::warning('Stripe webhook: pago fallido', [
                    'id' => $event->data->object->id,
                ]);
                break;

            case 'charge.refunded':
                Log::info('Stripe webhook: pago reembolsado', [
                    'id' => $event->data->object->id,
                ]);
                break;

            default:
                Log::info('Stripe webhook: evento no manejado', [
                    'type' => $event->type,
                ]);
        }

        return response('Webhook recibido', Response::HTTP_OK);
    }
}
