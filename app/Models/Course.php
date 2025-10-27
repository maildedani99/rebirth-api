<?php
// app/Models/Course.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'content',
    ];

    protected $casts = [
        'price'   => 'decimal:2',
        'content' => 'array', // se serializa/deserializa JSON automáticamente
    ];

    public function users()
{
    return $this->belongsToMany(User::class, 'course_user')
        ->withPivot(['enrolled_at', 'status', 'price_cents'])
        ->withTimestamps();
}

}

