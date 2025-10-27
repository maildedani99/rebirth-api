<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id','course_id','amount_cents','currency','status',
        'method','paid_at','reference','notes',
        // 'metadata', // añade si algún día creas la columna JSON
    ];

    protected $casts = [
        'paid_at'      => 'datetime',
        'amount_cents' => 'integer',
        // 'metadata'   => 'array',
    ];

    // ===== Relaciones =====
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // Alias opcional por compatibilidad
    public function user()
    {
        return $this->client();
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // ===== Scopes útiles (opcionales) =====
    public function scopePaid($q)
    {
        return $q->where('status', 'paid');
    }

    public function scopeForClient($q, int $clientId)
    {
        return $q->where('client_id', $clientId);
    }

    // ===== Atributos virtuales (opcionales) =====
    protected $appends = ['amount_eur'];

    public function getAmountEurAttribute(): string
    {
        return number_format(($this->amount_cents ?? 0) / 100, 2, '.', '');
    }
}
