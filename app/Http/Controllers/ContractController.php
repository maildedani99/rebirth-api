<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\ApiResponse;

class ContractController extends Controller
{
    public function acceptCourse(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth('api')->user();
        if (!$user) return ApiResponse::error('No autorizado', 401);

        // idempotente: si ya estaba aceptado, devolvemos OK sin romper
        if ($user->contractSigned) {
            return ApiResponse::ok([
                'user' => $user,
                'alreadyAccepted' => true,
            ], 'Contrato ya estaba aceptado');
        }

        // Guarda evidencias mínimas
        $user->contractSigned = true;
        $user->contractDate   = now();
        $user->contractIp     = $request->ip();
        // Si quieres guardar más (aunque no tengas columnas):
        // $user->contractUA  = $request->input('userAgent');  // necesitarías columna
        // $user->contractTZ  = $request->input('timeZone');   // necesitarías columna
        $user->save();

        return ApiResponse::ok([
            'user' => $user,
        ], 'Contrato aceptado');
    }
}
