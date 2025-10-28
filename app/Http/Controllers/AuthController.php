<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\MustVerifyEmail; // para instanceof
use Illuminate\Auth\Events\Verified;
use App\Support\ApiResponse;

class AuthController extends Controller
{
    /**
     * Registro de usuario + login + envío de verificación (si procede).
     */
    public function register(Request $request)
    {
        // Normaliza
        $request->merge([
            'firstName'  => trim((string)$request->input('firstName')),
            'lastName'   => trim((string)$request->input('lastName')),
            'email'      => strtolower(trim((string)$request->input('email'))),
            'password'   => (string)$request->input('password'),
            'password_confirmation' => (string)$request->input('password_confirmation'),
            'dni'        => strtoupper(preg_replace('/\s+/', '', (string)$request->input('dni'))),
            'phone'      => preg_replace('/\s+/', '', (string)$request->input('phone')),
            'address'    => trim((string)$request->input('address')),
            'city'       => trim((string)$request->input('city')),
            'postalCode' => trim((string)$request->input('postalCode')),
            'province'   => trim((string)$request->input('province')),
            'birthDate'  => trim((string)$request->input('birthDate')),
            'country'    => trim((string)$request->input('country', 'España')),
            'marketingConsent' => (bool)$request->boolean('marketingConsent', false),
        ]);

        // Validación
        $validated = $request->validate([
            'firstName'       => ['required','string','max:255'],
            'lastName'        => ['required','string','max:255'],
            'email'           => ['required','string','email','max:255','unique:users,email'],
            'password'        => ['required','confirmed', Rules\Password::defaults()],
            'dni'             => ['required','string','max:20','unique:users,dni'],
            'phone'           => ['required','regex:/^(\+34\s?)?\d{9}$/'],
            'address'         => ['required','string','max:255'],
            'city'            => ['required','string','max:255'],
            'postalCode'      => ['required','regex:/^\d{5}$/'],
            'province'        => ['required','string','max:255'],
            'birthDate'       => ['required','date'],
            'country'         => ['required','string','max:80'],
            'marketingConsent'=> ['sometimes','boolean'],
        ], [
            'postalCode.regex' => 'El C.P. debe tener 5 dígitos.',
            'phone.regex'      => 'Teléfono inválido (9 dígitos, opcional +34).',
        ]);

        // Crear usuario con tus columnas (camelCase)
        $user = User::create([
            'firstName'        => $validated['firstName'],
            'lastName'         => $validated['lastName'],
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'dni'              => $validated['dni'],
            'phone'            => $validated['phone'],
            'address'          => $validated['address'],
            'city'             => $validated['city'],
            'postalCode'       => $validated['postalCode'],
            'province'         => $validated['province'],
            'birthDate'        => $validated['birthDate'],
            'country'          => $validated['country'],
            // Estados por defecto
            'role'             => 'client',
            'isActive'         => false,
            'status'           => 'pending',
            'coursePriceCents' => 0,
            'tutor_id'         => null,
            'depositStatus'    => 'pending',
            'finalPayment'     => 'pending',
            'contractSigned'   => false,
            'marketingConsent' => $validated['marketingConsent'] ?? false,
        ]);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $token = $guard->login($user);

        // 📧 Enviar verificación de email si el modelo lo requiere
        if ($user instanceof MustVerifyEmail) {
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $e) {
                Log::error('Error enviando verificación de email: '.$e->getMessage());
                // No interrumpimos el registro si falla SMTP
            }
        }

        return ApiResponse::created([
            'token'          => $token,
            'token_type'     => 'bearer',
            'expires_in'     => $guard->factory()->getTTL() * 60,
            'user'           => $guard->user(),
            'email_verified' => (bool) $user->hasVerifiedEmail(),
        ], 'Usuario registrado con éxito. Te hemos enviado un correo para verificar tu email.');
    }

    /**
     * Login con JWT.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required','email'],
            'password' => ['required','string'],
        ]);

        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        $token = $guard->attempt($credentials);
        if (!$token) {
            return ApiResponse::error('Credenciales inválidas', 401);
        }

        $user = $guard->user();

        return ApiResponse::ok([
            'token'          => $token,
            'token_type'     => 'bearer',
            'expires_in'     => $guard->factory()->getTTL() * 60,
            'user'           => $user,
            'email_verified' => (bool) optional($user)->hasVerifiedEmail(),
        ], 'Login correcto');
    }

    /**
     * Perfil (requiere jwt.auth).
     */
    public function me()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        return ApiResponse::ok($guard->user(), 'Perfil');
    }

    /**
     * Logout (invalida el token actual).
     */
    public function logout()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $guard->logout();

        return ApiResponse::ok(null, 'Logout OK');
    }

    /**
     * Alta de usuario por admin (sin login ni verificación automática).
     */
    public function store(Request $request)
    {
        // Normaliza: acepta snake_case o camelCase y limpia
        $request->merge([
            'firstName'   => trim((string)($request->input('firstName')   ?? $request->input('first_name'))),
            'lastName'    => trim((string)($request->input('lastName')    ?? $request->input('last_name'))),
            'email'       => strtolower(trim((string)$request->input('email'))),
            'role'        => trim((string)$request->input('role')),
            'password'    => (string)$request->input('password'),
            'password_confirmation' => (string)$request->input('password_confirmation'),
            'dni'         => strtoupper(preg_replace('/\s+/', '', (string)$request->input('dni'))),
            'phone'       => preg_replace('/\s+/', '', (string)$request->input('phone')),
            'address'     => trim((string)$request->input('address')),
            'city'        => trim((string)$request->input('city')),
            'postalCode'  => trim((string)($request->input('postalCode')  ?? $request->input('postal_code'))),
            'province'    => trim((string)$request->input('province')),
            'birthDate'   => trim((string)($request->input('birthDate')   ?? $request->input('birth_date'))),
            'country'     => trim((string)$request->input('country', 'España')),
            'isActive'    => $request->boolean('isActive', true),
        ]);

        // Validación
        $validated = $request->validate([
            'firstName'      => ['required','string','max:255'],
            'lastName'       => ['required','string','max:255'],
            'email'          => ['required','string','email','max:255','unique:users,email'],
            'role'           => ['required','in:admin,teacher,client'],
            'password'       => ['required','confirmed', Rules\Password::defaults()],
            'dni'            => ['required','string','max:20','unique:users,dni'],
            'phone'          => ['required','regex:/^(\+34\s?)?\d{9}$/'],
            'address'        => ['required','string','max:255'],
            'city'           => ['required','string','max:255'],
            'postalCode'     => ['required','regex:/^\d{5}$/'],
            'province'       => ['required','string','max:255'],
            'birthDate'      => ['required','date'],
            'country'        => ['required','string','max:80'],
            'isActive'       => ['sometimes','boolean'],
        ], [
            'postalCode.regex' => 'El C.P. debe tener 5 dígitos.',
            'phone.regex'      => 'Teléfono inválido (9 dígitos, opcional +34).',
        ]);

        // Crea el usuario (camelCase)
        $user = User::create([
            'firstName'   => $validated['firstName'],
            'lastName'    => $validated['lastName'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'dni'         => $validated['dni'],
            'phone'       => $validated['phone'],
            'address'     => $validated['address'],
            'city'        => $validated['city'],
            'postalCode'  => $validated['postalCode'],
            'province'    => $validated['province'],
            'birthDate'   => $validated['birthDate'],
            'country'     => $validated['country'],
            'role'        => $validated['role'],
            'isActive'    => (bool)($validated['isActive'] ?? true),
            // defaults
            'status'           => 'pending',
            'coursePriceCents' => 0,
            'tutor_id'         => null,
            'depositStatus'    => 'pending',
            'finalPayment'     => 'pending',
            'contractSigned'   => false,
            'marketingConsent' => false,
        ]);

        return ApiResponse::created($user, 'Usuario creado correctamente');
    }

    /**
     * Refresh de token.
     */
    public function refresh()
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $new = $guard->refresh();

        return ApiResponse::ok([
            'token'      => $new,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
        ], 'Token refrescado');
    }

    /**
     * Verificación de email "stateless" (sin JWT): la ruta usa middleware 'signed'.
     * GET /api/email/verify/{id}/{hash}
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::error('Usuario no encontrado', 404);
        }

        // Validación del hash de email (sha1 del email verificado por Laravel)
        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return ApiResponse::error('Hash inválido', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $front = env('FRONTEND_URL', 'http://localhost:3000');
        return redirect($front . '/verified?status=success');
    }

    /**
     * Reenviar correo de verificación (requiere jwt.auth).
     * POST /api/email/verification-notification
     */
    public function resendVerification(Request $request)
    {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $user = $guard->user();

        if (! $user) {
            return ApiResponse::error('Unauthenticated', 401);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::ok(null, 'Email already verified');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $e) {
            Log::error('Error reenviando verificación: '.$e->getMessage());
            return ApiResponse::error('No se pudo enviar el correo', 500);
        }

        return ApiResponse::ok(null, 'Verification link sent');
    }
}
