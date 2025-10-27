<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Config;
use App\Models\User;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * GET /api/courses
     * Lista de cursos (opcional: search, paginación).
     */
   public function index(Request $request)
{
    $perPage = (int) ($request->get('per_page', 15));
    $search  = trim((string) $request->get('search', ''));

    $q = Course::query();

    if ($search !== '') {
        $q->where(function ($qq) use ($search) {
            $qq->where('name', 'like', "%{$search}%")
               ->orWhere('description', 'like', "%{$search}%");
        });
    }

    $page = $q->orderByDesc('id')->paginate($perPage);

    // 👇 Envolver en "data" para que tu fetcher devuelva el objeto completo
    return response()->json([
        'data' => $page
    ]);
}

    /**
     * GET /api/courses/{id}
     * Devuelve un curso concreto.
     */
    public function show(Course $course)
    {
        return response()->json($course);
    }

    /**
     * POST /api/courses
     * Crea un curso con precio por defecto si no viene.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'content'     => 'nullable|array',
        ]);

        $cfg = method_exists(Config::class, 'firstOrCreate')
            ? Config::firstOrCreate(['singleton' => 'X'], ['stripe_default_region' => 'es'])
            : Config::first();

        if (!isset($data['price']) || $data['price'] === null || $data['price'] === '') {
            $data['price'] = $cfg?->price_course ?? 0;
        }

        $course = Course::create($data);

        return response()->json($course, 201);
    }

    /**
     * PUT /api/courses/{id}
     * Actualiza un curso.
     */
    public function update(Request $request, Course $course)
    {
        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'nullable|numeric|min:0',
            'content'     => 'nullable|array',
        ]);

        $course->update($data);

        return response()->json($course);
    }

    /**
     * DELETE /api/courses/{id}
     * Elimina un curso.
     */
    public function destroy(Course $course)
    {
        $course->delete();
        return response()->json(['ok' => true]);
    }


     public function students(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        $q = trim((string)$request->get('q', ''));
        $perPage = max(1, min(100, (int)$request->get('per_page', 20)));

        $users = $course->users()
            ->when($q !== '', function ($qb) use ($q) {
                $qb->where(function ($w) use ($q) {
                    $w->where('firstName', 'like', "%{$q}%")
                      ->orWhere('lastName', 'like', "%{$q}%")
                      ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('users.lastName')
            ->paginate($perPage);

        return ApiResponse::ok($users);
    }

    /**
     * POST /api/courses/{course}/enroll
     * body: { user_id, status?, price_cents? }
     */
    public function enroll(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        $data = $request->validate([
            'user_id'     => ['required', 'exists:users,id'],
            'status'      => ['sometimes', Rule::in(['active','cancelled','completed'])],
            'price_cents' => ['sometimes','nullable','integer','min:0'],
        ]);

        $user = User::findOrFail($data['user_id']);

        // Evita duplicados (único en migración, pero controlamos aquí también)
        if ($course->users()->where('users.id', $user->id)->exists()) {
            return ApiResponse::ok(null, 'El usuario ya está inscrito en este curso');
        }

        $course->users()->attach($user->id, [
            'enrolled_at' => Carbon::now(),
            'status'      => $data['status']      ?? 'active',
            'price_cents' => $data['price_cents'] ?? null,
        ]);

        // Opcional: devolver la inscripción o el usuario
        return ApiResponse::created([
            'course_id'   => (int)$courseId,
            'user_id'     => (int)$user->id,
            'enrolled_at' => Carbon::now()->toISOString(),
            'status'      => $data['status']      ?? 'active',
            'price_cents' => $data['price_cents'] ?? null,
        ], 'Inscripción creada');
    }

    /**
     * DELETE /api/courses/{course}/students/{user}
     */
    public function unenroll($courseId, $userId)
    {
        $course = Course::findOrFail($courseId);
        $user   = User::findOrFail($userId);

        $course->users()->detach($user->id);

        return ApiResponse::ok(null, 'Inscripción eliminada');
    }
}


