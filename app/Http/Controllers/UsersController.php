<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Support\ApiResponse;

class UsersController extends Controller
{
    /**
     * Lista de clientes (role = client)
     * GET /api/users/clients?search=...
     */
  public function listClients(Request $request)
{
    $search = trim((string) $request->query('search', ''));

    $query = User::query()
        ->where('role', 'client');

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('firstName', 'like', "%{$search}%")
              ->orWhere('lastName', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $rows = $query
        ->orderByDesc('created_at')
        ->get([
            'id',
            'firstName',
            'lastName',
            'email',
            'role',
            'isActive',
            // usamos created_at como "último acceso" de momento
            'created_at as lastLogin',
        ]);

    return ApiResponse::ok($rows, 'Clients list');
}

public function listMembers(Request $request)
{
    $search = trim((string) $request->query('search', ''));

    $query = User::query()
        ->whereIn('role', ['admin', 'teacher']);

    if ($search !== '') {
        $query->where(function ($q) use ($search) {
            $q->where('firstName', 'like', "%{$search}%")
              ->orWhere('lastName', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    $rows = $query
        ->orderByDesc('created_at')
        ->get([
            'id',
            'firstName',
            'lastName',
            'email',
            'role',
            'isActive',
            'created_at as lastLogin',
        ]);

    return ApiResponse::ok($rows, 'Members list');
}
public function show($id)
{
    $user = User::find($id);

    if (!$user) {
        return ApiResponse::error('Usuario no encontrado', 404);
    }

    return ApiResponse::ok($user, 'Usuario obtenido correctamente');
}
}
