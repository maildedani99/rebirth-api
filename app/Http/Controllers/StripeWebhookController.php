<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment; // o el modelo que uses
use Stripe\Stripe;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Cargar la clave secreta del webhook
        $endpoint_secret = config('services.stripe.webhook_secret') ?? env('STRIPE_WEBHOOK_SECRET');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook payload inválido', ['error' => $e->getMessage()]);
            return response('Invalid payload', Response::HTTP_BAD_REQUEST);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Firma de Stripe no válida', ['error' => $e->getMessage()]);
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        // Procesar el tipo de evento
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'payment_intent.succeeded':
                $data = $event->data->object;
                $paymentIntentId = $data->id ?? null;

                // Aquí puedes buscar el pago por metadata o ID
                if (isset($data->metadata['payment_id'])) {
                    $payment = Payment::find($data->metadata['payment_id']);
                    if ($payment) {
                        $payment->status = 'paid';
                        $payment->paid_at = now();
                        $payment->save();
                    }
                }

                Log::info('Pago confirmado desde Stripe', ['id' => $paymentIntentId]);
                break;

            case 'payment_intent.payment_failed':
                Log::warning('Pago fallido', ['id' => $event->data->object->id]);
                break;

            case 'charge.refunded':
                Log::info('Pago reembolsado', ['id' => $event->data->object->id]);
                break;

            default:
                Log::info('Evento no manejado: ' . $event->type);
        }

        return response('Webhook recibido', Response::HTTP_OK);
    }
}
