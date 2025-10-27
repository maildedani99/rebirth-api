<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PaymentController extends Controller
{
    private const ALLOWED_STATUSES = ['pending', 'paid', 'canceled'];

    // GET /api/payments
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 20);

        $q = Payment::query()
            ->with(['client:id,firstName,lastName,email', 'course:id,name'])
            ->when($request->filled('client_id'), fn($qq) => $qq->where('client_id', $request->client_id))
            ->when($request->filled('course_id'), fn($qq) => $qq->where('course_id', $request->course_id))
            ->when($request->filled('status'), fn($qq)    => $qq->where('status', $request->status))
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'data'    => $q->paginate($perPage),
        ]);
    }

    // POST /api/payments
    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'    => ['required','exists:users,id'],
            'course_id'    => ['nullable','exists:courses,id'],
            'amount_cents' => ['required','integer','min:1'],
            'currency'     => ['nullable','string','size:3'],
            'status'       => ['nullable', Rule::in(self::ALLOWED_STATUSES)],
            'method'       => ['nullable','string','max:50'],
            'paid_at'      => ['nullable','date'],
            'reference'    => ['nullable','string','max:255'],
            'notes'        => ['nullable','string'],
        ]);

        $data['currency'] = $data['currency'] ?? 'EUR';
        $data['status']   = $data['status']   ?? 'pending';

        // Si se crea como pagado, coherencia con paid_at
        if ($data['status'] === 'paid' && empty($data['paid_at'])) {
            $data['paid_at'] = Carbon::now();
        }

        $payment = Payment::create($data)->load(['client:id,firstName,lastName,email', 'course:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Payment created',
        ], 201);
    }

    // GET /api/payments/{payment}
    public function show(Payment $payment)
    {
        $payment->load(['client:id,firstName,lastName,email', 'course:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $payment,
        ]);
    }

    // PUT/PATCH /api/payments/{payment}
    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'client_id'    => ['sometimes','exists:users,id'],
            'course_id'    => ['sometimes','nullable','exists:courses,id'],
            'amount_cents' => ['sometimes','integer','min:1'],
            'currency'     => ['sometimes','string','size:3'],
            'status'       => ['sometimes', Rule::in(self::ALLOWED_STATUSES)],
            'method'       => ['sometimes','nullable','string','max:50'],
            'paid_at'      => ['sometimes','nullable','date'],
            'reference'    => ['sometimes','nullable','string','max:255'],
            'notes'        => ['sometimes','nullable','string'],
        ]);

        // Coherencia de paid_at según status
        if (isset($data['status'])) {
            if ($data['status'] === 'paid') {
                $data['paid_at'] = $data['paid_at'] ?? Carbon::now();
            } else {
                // pending o canceled => no debe quedar paid_at
                $data['paid_at'] = null;
            }
        }

        $payment->update($data);
        $payment->load(['client:id,firstName,lastName,email', 'course:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Payment updated',
        ]);
    }

    // DELETE /api/payments/{payment}
    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted',
        ]);
    }

    // POST /api/payments/{payment}/mark-paid
    // Permite pending|canceled -> paid (si ya paid, idempotente)
    public function markPaid(Request $request, Payment $payment)
    {
        if ($payment->status === 'paid') {
            return response()->json([
                'success' => true,
                'data'    => $payment,
                'message' => 'Already paid',
            ]);
        }

        $data = $request->validate([
            'paid_at'   => ['sometimes','nullable','date'],
            'method'    => ['sometimes','nullable','string','max:50'],
            'reference' => ['sometimes','nullable','string','max:255'],
            'notes'     => ['sometimes','nullable','string'],
        ]);

        $payment->status    = 'paid';
        $payment->paid_at   = isset($data['paid_at']) ? Carbon::parse($data['paid_at']) : Carbon::now();
        $payment->method    = $data['method']    ?? $payment->method;
        $payment->reference = $data['reference'] ?? $payment->reference;

        if (array_key_exists('notes', $data)) {
            $payment->notes = trim(($payment->notes ? $payment->notes . "\n" : '') . $data['notes']);
        }

        $payment->save();
        $payment->load(['client:id,firstName,lastName,email', 'course:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Payment marked as paid',
        ]);
    }

    // POST /api/payments/{payment}/cancel
    public function cancel(Request $request, Payment $payment)
    {
        if ($payment->status === 'canceled') {
            return response()->json([
                'success' => true,
                'data'    => $payment,
                'message' => 'Already canceled',
            ]);
        }

        $data = $request->validate([
            'reason'    => ['sometimes','nullable','string','max:500'],
            'reference' => ['sometimes','nullable','string','max:255'],
            'notes'     => ['sometimes','nullable','string'],
        ]);

        $notes = $payment->notes ? $payment->notes . "\n" : '';
        if (!empty($data['reason'])) {
            $notes .= '[CANCELED] ' . $data['reason'];
        }
        if (array_key_exists('notes', $data) && !empty($data['notes'])) {
            $notes .= (empty($notes) ? '' : "\n") . trim($data['notes']);
        }

        $payment->status    = 'canceled';
        $payment->paid_at   = null; // coherencia: si se anula, ya no queda marcado como pagado
        $payment->reference = $data['reference'] ?? $payment->reference;
        $payment->notes     = $notes ?: $payment->notes;

        $payment->save();
        $payment->load(['client:id,firstName,lastName,email', 'course:id,name']);

        return response()->json([
            'success' => true,
            'data'    => $payment,
            'message' => 'Payment canceled',
        ]);
    }

    // 🔁 Alias de compatibilidad: si hay clientes llamando a /refund, lo tratamos como cancelación
    // POST /api/payments/{payment}/refund
    public function refund(Request $request, Payment $payment)
    {
        return $this->cancel($request, $payment);
    }

    /**
     * GET /api/users/{id}/balances
     * Opcional: ?course_id=NN filtra a un solo curso
     */
    public function balancesForClient(Request $request, int $id)
    {
        $user = User::with(['courses' => function ($q) {
            $q->select('courses.id', 'courses.name', 'courses.price_cents')
              ->withPivot(['price_cents', 'status', 'enrolled_at']);
        }])->findOrFail($id);

        $filterCourseId = $request->integer('course_id');
        $courses = collect($user->courses);
        if ($filterCourseId) {
            $courses = $courses->where('id', $filterCourseId)->values();
        }

        $courseIds = $courses->pluck('id')->all();
        if (empty($courseIds)) {
            return response()->json([
                'success' => true,
                'data'    => [
                    'courses' => [],
                    'summary' => [
                        'total_price_cents' => 0,
                        'total_paid_cents'  => 0,
                        'total_remaining_cents' => 0,
                    ],
                ],
            ]);
        }

        // Solo suman pagos 'paid'
        $paidByCourse = Payment::selectRaw('course_id, SUM(amount_cents) as paid_cents')
            ->where('client_id', $user->id)
            ->where('status', 'paid')
            ->whereIn('course_id', $courseIds)
            ->groupBy('course_id')
            ->pluck('paid_cents', 'course_id');

        $rows = [];
        $totalPrice = 0;
        $totalPaid  = 0;

        foreach ($courses as $c) {
            $price = (int) ($c->pivot->price_cents ?? $c->price_cents ?? 0);
            $paid  = (int) ($paidByCourse[$c->id] ?? 0);

            $rows[] = [
                'course_id'       => (int) $c->id,
                'course'          => $c->name,
                'price_cents'     => $price,
                'paid_cents'      => $paid,
                'remaining_cents' => max(0, $price - $paid),
                'is_paid'         => $price > 0 ? $paid >= $price : false,
            ];

            $totalPrice += $price;
            $totalPaid  += $paid;
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'courses' => $rows,
                'summary' => [
                    'total_price_cents'     => $totalPrice,
                    'total_paid_cents'      => $totalPaid,
                    'total_remaining_cents' => max(0, $totalPrice - $totalPaid),
                ],
            ],
        ]);
    }
}
