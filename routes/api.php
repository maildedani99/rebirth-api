<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use Illuminate\Auth\Events\Verified;
use App\Models\User;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

Route::get('/egress-ip', function () {
    try {
        $ip = Http::timeout(5)->get('https://api.ipify.org')->body();

        return response()->json([
            'ok' => true,
            'egress_ip' => trim($ip),
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});


Route::get('/health', function (Request $request) {
    try {
        $start = microtime(true);
        DB::connection()->getPdo();
        $ms = (microtime(true) - $start) * 1000;

        return response()->json([
            'ok'   => true,
            'db'   => 'connected',
            'time' => now()->toDateTimeString(),
            'db_ms' => round($ms, 2),
        ]);
    } catch (\Throwable $e) {
        Log::error('Healthcheck DB error', [
            'exception' => $e,
        ]);

        return response()->json([
            'ok'  => false,
            'db'  => 'error',
            'msg' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/db-test', function () {
    try {
        DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        return response()->json([
            'ok' => true,
            'database' => $dbName,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

// Healthcheck
Route::get('ping', fn() => response()->json(['ok' => true, 'time' => now()]));

// ✅ STRIPE WEBHOOK (NO JWT)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');



// Auth públicas
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login',    [AuthController::class, 'login'])->name('auth.login');
});




/* -------------------------------------------------------------------------- */
/* 1) Verificar email (URL firmada: sin JWT, redirige al front)               */
/* -------------------------------------------------------------------------- */
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::find($id);
    if (! $user) {
        return response()->json(['message' => 'Usuario no encontrado.'], 404);
    }

    // Validar hash del email para seguridad extra
    if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        return response()->json(['message' => 'Hash inválido.'], 403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    // Redirigir al front
    $front = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
    return redirect($front . '/auth/verified?status=success');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

/* -------------------------------------------------------------------------- */
/* 2) Reenviar correo de verificación (requiere JWT)                          */
/* -------------------------------------------------------------------------- */

Route::post('/email/verification-notification', function (Request $request) {
    $user = $request->user();   // ✅ Intelephense lo entiende
    if (!$user) return response()->json(['message' => 'Unauthenticated'], 401);
    if ($user->hasVerifiedEmail()) return response()->json(['message' => 'Email already verified']);

    $user->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent']);
})->middleware(['jwt.auth', 'throttle:6,1'])->name('verification.send');

/* -------------------------------------------------------------------------- */
/*                      RUTAS PROTEGIDAS (JWT requerido)                      */
/* -------------------------------------------------------------------------- */
Route::middleware('jwt.auth')->group(function () {

    /* ----------------------------- AUTH protegidas ----------------------------- */
    Route::prefix('auth')->group(function () {
        Route::get('me',       [AuthController::class, 'me'])->name('auth.me');
        Route::post('logout',  [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    /* ----------------------------- STRIPE (JWT) ----------------------------- */
    // Crear sesión de Checkout (usuario autenticado, no requiere email verificado)
    Route::prefix('stripe')->group(function () {
        Route::post('create-checkout', [StripeController::class, 'createCheckout'])
            ->name('stripe.createCheckout');
    });


    /* ---------------------------------------------------------------------- */
    /*    Rutas que requieren email verificado además de JWT (verified)      */
    /* ---------------------------------------------------------------------- */
    Route::middleware('verified')->group(function () {

        /* ------------------ CONTRATOS (usuario autenticado) ------------------ */
        Route::prefix('contracts')->group(function () {
            Route::post('course/accept', [ContractController::class, 'acceptCourse'])->name('contracts.course.accept');
            Route::post('lopd/accept', [ContractController::class, 'acceptLopd'])
                ->name('contracts.lopd.accept');

            /* Route::post('users/{id}/lopd', [UsersController::class, 'acceptLopd']); */
        });

        /* -------------------------- ZONA SOLO ADMIN -------------------------- */
        Route::middleware('admin')->group(function () {

            // CONFIGURACIÓN
            Route::get('config', [ConfigController::class, 'show'])->name('config.show');
            Route::put('config', [ConfigController::class, 'update'])->name('config.update');

            // CURSOS
            Route::apiResource('courses', CourseController::class);
            Route::post('courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');

            // PAGOS (estados válidos: pending | paid | canceled)
            Route::apiResource('payments', PaymentController::class);
            Route::post('payments/{payment}/mark-paid', [PaymentController::class, 'markPaid'])->name('payments.markPaid');
            Route::post('payments/{payment}/cancel',    [PaymentController::class, 'cancel'])->name('payments.cancel');

            // Compatibilidad opcional: si ya existía este endpoint, tratar como cancelación
            Route::post('payments/{payment}/refund',    [PaymentController::class, 'refund'])->name('payments.refund');

            // USUARIOS
            Route::middleware('auth:api')->group(function () {
                Route::get('users/clients',  [UsersController::class, 'listClients'])->name('users.clients');
                Route::get('users/members',  [UsersController::class, 'listMembers'])->name('users.members');
            });
            Route::get('users/{id}',     [UsersController::class, 'show'])->name('users.show');
            Route::put('users/{id}',     [UsersController::class, 'update'])->name('users.update');
            Route::post('users',         [AuthController::class, 'store'])->name('users.store');
            Route::get('users/{id}/balances', [PaymentController::class, 'balancesForClient'])->name('users.balances');
        });


    });
});
