<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\ConfigController;

/**
 * Healthcheck simple
 * GET /api/ping
 */
Route::get('ping', fn () => response()->json(['ok' => true, 'time' => now()]));

/* -------------------------------------------------------------------------- */
/*                               AUTH PÚBLICAS                                */
/*     /api/auth/register  |  /api/auth/login                                 */
/* -------------------------------------------------------------------------- */
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('login',    [AuthController::class, 'login'])->name('auth.login');
});

/* -------------------------------------------------------------------------- */
/*                      VERIFICACIÓN DE EMAIL (Laravel)                       */
/* 1) El enlace del correo aterriza aquí (URL firmada)                        */
/* 2) Reenviar correo de verificación (requiere JWT)                          */
/* -------------------------------------------------------------------------- */

// 1) Verificar email (URL firmada que marca email_verified_at y redirige al front)
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill(); // marca como verificado
    return redirect(env('FRONTEND_URL', 'http://localhost:3000') . '/verified?status=success');
})->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

// 2) Reenviar correo de verificación (requiere JWT)
Route::post('/email/verification-notification', function () {
    $user = auth()->user(); // más robusto bajo jwt.auth
    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }
    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }
    $user->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent']);
})->middleware(['jwt.auth', 'throttle:6,1'])->name('verification.send');

/* -------------------------------------------------------------------------- */
/*                      RUTAS PROTEGIDAS (JWT requerido)                      */
/* -------------------------------------------------------------------------- */
Route::middleware('jwt.auth')->group(function () {

    /* ----------------------------- AUTH protegidas ----------------------------- */
    Route::prefix('auth')->group(function () {
        Route::get('me',       [AuthController::class, 'me'])->name('auth.me');      // perfil
        Route::post('logout',  [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('auth.refresh');
    });

    /* ---------------------------------------------------------------------- */
    /*    Rutas que requieren email verificado además de JWT (verified)      */
    /* ---------------------------------------------------------------------- */
    Route::middleware('verified')->group(function () {

        /* ------------------ CONTRATOS (usuario autenticado) ------------------ */
        Route::prefix('contracts')->group(function () {
            Route::post('course/accept', [ContractController::class, 'acceptCourse'])->name('contracts.course.accept');
            // Route::post('lopd/accept',  [ContractController::class, 'acceptLopd']);
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
            Route::get('users/clients',  [UsersController::class, 'listClients'])->name('users.clients');
            Route::get('users/members',  [UsersController::class, 'listMembers'])->name('users.members');
            Route::get('users/{id}',     [UsersController::class, 'show'])->name('users.show');
            Route::put('users/{id}',     [UsersController::class, 'update'])->name('users.update');
            Route::post('users',         [AuthController::class, 'store'])->name('users.store');
            Route::get('users/{id}/balances', [PaymentController::class, 'balancesForClient'])->name('users.balances');
        });
    });
});
