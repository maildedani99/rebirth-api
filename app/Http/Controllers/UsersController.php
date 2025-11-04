<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Registro
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
        ]);

        $user = User::create([
            'firstName' => $data['firstName'],
            'lastName'  => $data['lastName'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'client',
            'isActive'  => false,
            'status'    => 'pending',
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Usuario registrado. Verifica tu email para continuar.',
        ], 201);
    }

    /**
     * Login
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email no verificado',
                'needsVerification' => true,
            ], 403);
        }

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    /**
     * Verificar email
     */
    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect(env('FRONT_URL') . '/verified?status=already');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect(env('FRONT_URL') . '/verified?status=success');
    }

    /**
     * Reenviar link de verificación
     */
    public function resendVerificationEmail(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Ya verificado'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email reenviado']);
    }

    /**
     * Logout
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
}
