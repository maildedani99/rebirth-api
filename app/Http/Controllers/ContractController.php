<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    /**
     * Serializa el usuario a un array "plano" y seguro para JSON.
     * (Igual que hicimos en login: nada de relaciones raras).
     */
    protected function serializeUser(User $user): array
    {
        return [
            'id'               => $user->id,
            'email'            => $user->email,
            'firstName'        => $user->firstName ?? null,
            'lastName'         => $user->lastName ?? null,
            'role'             => $user->role ?? null,
            'isActive'         => (bool)($user->isActive ?? true),

            // Campos de progreso que seguramente usas en el front:
            'depositStatus'    => $user->depositStatus ?? null,
            'finalPayment'     => $user->finalPayment ?? null,
            'contractSigned'   => (bool)($user->contractSigned ?? false),
            'contractDate'     => $user->contractDate ?? null,
            'contractIp'       => $user->contractIp ?? null,
            'lopdAccepted'     => (bool)($user->lopdAccepted ?? false),
            'marketingConsent' => (bool)($user->marketingConsent ?? false),
        ];
    }

    public function acceptCourse(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = $request->user('api') ?? auth('api')->user();
            if (!$user) {
                return ApiResponse::error('No autorizado', 401);
            }

            // Idempotente: si ya estaba aceptado, devolvemos OK
            if ($user->contractSigned) {
                return ApiResponse::ok([
                    'user'            => $this->serializeUser($user),
                    'alreadyAccepted' => true,
                ], 'Contrato ya estaba aceptado');
            }

            // Guarda evidencias mínimas
            $user->contractSigned = true;
            $user->contractDate   = now();
            $user->contractIp     = $request->ip();
            $user->save();

            return ApiResponse::ok([
                'user'            => $this->serializeUser($user),
                'alreadyAccepted' => false,
            ], 'Contrato aceptado');
        } catch (\Throwable $e) {
            Log::error('Error en acceptCourse', [
                'user_id' => isset($user) && $user instanceof User ? $user->id : null,
                'msg'     => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Error interno al aceptar el contrato', 500);
        }
    }

    public function acceptLopd(Request $request)
    {
        try {
            /** @var User|null $user */
            $user = $request->user('api') ?? auth('api')->user();
            if (!$user) {
                return ApiResponse::error('No autorizado', 401);
            }

            $user->lopdAccepted     = true;
            $user->marketingConsent = (bool)$request->boolean('marketingConsent', false);
            $user->save();

            return ApiResponse::ok([
                'user'             => $this->serializeUser($user),
                'lopdAccepted'     => $user->lopdAccepted,
                'marketingConsent' => $user->marketingConsent,
            ], 'LOPD aceptada correctamente');
        } catch (\Throwable $e) {
            Log::error('Error en acceptLopd', [
                'user_id' => isset($user) && $user instanceof User ? $user->id : null,
                'msg'     => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return ApiResponse::error('Error interno al aceptar la LOPD', 500);
        }
    }
}
