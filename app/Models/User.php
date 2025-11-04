<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;   // 👈 necesario para verificación
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'firstName',
        'lastName',
        'email',
        'password',
        'dni',
        'phone',
        'address',
        'city',
        'postalCode',
        'province',
        'birthDate',
        'country',
        'role',
        'isActive',
        'status',
        'coursePriceCents',
        'tutor_id',
        'depositStatus',
        'finalPayment',
        'contractSigned',
        'marketingConsent',
        'email_verified_at',
        'lopdAccepted',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birthDate'        => 'date',
        'isActive'         => 'boolean',
        'contractSigned'   => 'boolean',
        'contractDate'     => 'datetime',
        'marketingConsent' => 'boolean',
        'coursePriceCents' => 'integer',
        'email_verified_at' => 'datetime',
        'depositStatus' => 'boolean',
        'finalPayment' => 'boolean',
        'depositStatus'    => 'boolean',
        'lopdAccepted'     => 'boolean',
    ];

    // --- Relaciones ---
    public function tutor()
    {
        // El tutor es otro usuario (role=teacher)
        return $this->belongsTo(User::class, 'tutor_id');
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class, 'client_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_user')
            ->withPivot(['enrolled_at', 'status', 'price_cents'])
            ->withTimestamps();
    }

    public function students()
    {
        // alumnos de un tutor
        return $this->hasMany(User::class, 'tutor_id');
    }

    // --- JWT ---
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'role' => $this->role,
            'email' => $this->email,
            'verified' => (bool) $this->email_verified_at, // 👈 útil en frontend
        ];
    }
}
