<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;




class UsersController extends Controller
{
    // Campos base (coinciden con el front) + created_at para mapear createdAt
    private array $select = [
        'id',
        'firstName',
        'lastName',
        'email',
        'phone',
        'address',
        'city',
        'postalCode',
        'country',
        'dni',
        'role',
        'isActive',
        'status',
        'coursePriceCents',
        'tutor_id',
        'depositStatus',
        'finalPayment',
        'contractSigned',
        'contractDate',
        'contractIp',
        'created_at',
    ];

    private function baseQuery(Request $request)
    {
        $q = trim((string) $request->query('search', ''));
        return User::query()
            ->when($q !== '', function ($query) use ($q) {
                $like = "%{$q}%";
                $query->where(function ($w) use ($like) {
                    $w->where('firstName', 'like', $like)
                        ->orWhere('lastName',  'like', $like)
                        ->orWhere('email',     'like', $like)
                        ->orWhere('dni',       'like', $like);
                });
            })
            ->orderByDesc('created_at');
    }

    /** GET /api/users/clients */
    public function listClients(Request $request)
    {
        $items = $this->baseQuery($request)
            ->where('role', 'client')
            ->get($this->select)
            ->map(fn(User $u) => $this->serialize($u))
            ->values();

        return ApiResponse::ok($items, 'Clients fetched');
    }

    /** GET /api/users/members (admin|teacher) */
    public function listMembers(Request $request)
    {
        $items = $this->baseQuery($request)
            ->where('role', '!=', 'client') // admin + teacher
            ->get($this->select)
            ->map(fn(User $u) => $this->serialize($u))
            ->values();

        return ApiResponse::ok($items, 'Members fetched');
    }

    /** GET /api/users/{id} */
  /** GET /api/users/{id} */
public function show(int $id)
{
    $user = User::query()
        ->select($this->select) // ← asegura que traes address, city, etc.
        ->with([
            'tutor:id,firstName,lastName,email',
            'courses:id,name',
            'payments' => function ($q) {
                $q->select(
                    'id',
                    'client_id',   // importante: no user_id
                    'course_id',
                    'amount_cents',
                    'currency',
                    'status',
                    'method',
                    'paid_at',
                    'reference',
                    'created_at'
                )->with(['course:id,name']);
            },
        ])
        ->find($id);

    if (!$user) {
        return ApiResponse::notFound('User not found');
    }

    // Base con TODOS los campos (address, city, postalCode, country, dni, ...)
    $out = $this->serialize($user);

    // Tutor (si aplica)
    $out['tutor'] = $user->tutor ? [
        'id'        => $user->tutor->id,
        'firstName' => $user->tutor->firstName,
        'lastName'  => $user->tutor->lastName,
        'email'     => $user->tutor->email,
    ] : null;

    // Cursos
    $out['courses'] = $user->courses
        ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
        ->values();

    // Pagos
    $out['payments'] = $user->payments
        ->map(function ($p) {
            return [
                'id'           => $p->id,
                'course_id'    => $p->course_id,
                'course'       => $p->course ? ['id' => $p->course->id, 'name' => $p->course->name] : null,
                'amount_cents' => (int) $p->amount_cents,
                'currency'     => $p->currency,
                'status'       => $p->status,
                'method'       => $p->method,
                'paid_at'      => optional($p->paid_at)->toISOString(),
                'reference'    => $p->reference,
                'created_at'   => optional($p->created_at)->toISOString(),
            ];
        })
        ->values();

    return ApiResponse::ok($out, 'User fetched');
}



    /** GET /api/dev/wipe-users (solo desarrollo) */
    public function wipe()
    {
        User::query()->delete();
        return ApiResponse::ok(null, 'Todos los usuarios han sido eliminados.');
    }

    /** ---- Helpers ---- */

    private function serialize(User $u): array
    {
        return [
            'id'               => $u->id,
            'firstName'        => $u->firstName,
            'lastName'         => $u->lastName,
            'email'            => $u->email,
            'phone'            => $u->phone,
            'address'          => $u->address,
            'city'             => $u->city,
            'postalCode'       => $u->postalCode,
            'country'          => $u->country,
            'dni'              => $u->dni,
            'role'             => $u->role,                  // admin|teacher|client
            'isActive'         => (bool) $u->isActive,
            'status'           => $u->status,
            'coursePriceCents' => (int) ($u->coursePriceCents ?? 0),
            'tutor_id'         => $u->tutor_id,              // null si no tiene
            'depositStatus'    => $u->depositStatus,
            'finalPayment'     => $u->finalPayment,
            'contractSigned'   => (bool) $u->contractSigned,
            'contractDate'     => $u->contractDate,
            'contractIp'       => $u->contractIp,
            'createdAt'        => optional($u->created_at)->toISOString(),
        ];
    }



    // ...

    /** PUT /api/users/{id} */
    public function update(Request $request, int $id)
    {
        $user = User::query()->find($id);
        if (!$user) {
            return ApiResponse::notFound('User not found');
        }

        // ✅ Validación alineada con el FE (ClientView)
        $data = $request->validate([
            'firstName'   => ['required', 'string', 'max:120'],
            'lastName'    => ['required', 'string', 'max:120'],
            'email'       => [
                'required',
                'email',
                'max:190',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone'       => ['nullable', 'string', 'max:60'],
            'address'     => ['nullable', 'string', 'max:190'],
            'city'        => ['nullable', 'string', 'max:120'],
            'postalCode'  => ['nullable', 'string', 'max:20'],
            'country'     => ['nullable', 'string', 'max:80'],
            'dni'         => ['nullable', 'string', 'max:60'],

            'role'        => ['required', Rule::in(['admin', 'teacher', 'client'])],
            'status'      => ['required', Rule::in(['active', 'pending', 'blocked'])],
            'isActive'    => ['required', 'boolean'],

            'tutor_id'    => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // (Opcional) impedir que un usuario se cambie a sí mismo el rol
        // if (auth('api')->id() === $user->id && $data['role'] !== $user->role) {
        //     return ApiResponse::error('No puedes cambiar tu propio rol', 422);
        // }

        // ✅ Asignación segura de campos
        $user->firstName   = $data['firstName'];
        $user->lastName    = $data['lastName'];
        $user->email       = $data['email'];
        $user->phone       = $data['phone'] ?? null;
        $user->address     = $data['address'] ?? null;
        $user->city        = $data['city'] ?? null;
        $user->postalCode  = $data['postalCode'] ?? null;
        $user->country     = $data['country'] ?? null;
        $user->dni         = $data['dni'] ?? null;

        $user->role        = $data['role'];             // admin|teacher|client
        $user->status      = $data['status'];           // active|pending|blocked
        $user->isActive    = (bool) $data['isActive'];

        $user->tutor_id    = $data['tutor_id'] ?? null; // puede ser null

        $user->save();

        // 🔄 Recarga con relaciones y select predefinido para serializar igual que show()
        $reloaded = User::query()
            ->with(['tutor:id,firstName,lastName,email'])
            ->find($user->id, $this->select);

        $out = $this->serialize($reloaded);

        if ($reloaded->role === 'client') {
            $t = $reloaded->tutor; // puede ser null
            $out['tutor'] = $t ? [
                'id'        => $t->id,
                'firstName' => $t->firstName,
                'lastName'  => $t->lastName,
                'email'     => $t->email,
            ] : null;
        }

        return ApiResponse::ok($out, 'User updated');
    }
}
