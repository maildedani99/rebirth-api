<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_user', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('course_id')
                  ->constrained('courses')
                  ->cascadeOnDelete();

            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // Extras útiles
            $table->timestamp('enrolled_at')->nullable();
            $table->string('status', 20)->default('active'); // active | cancelled | completed
            $table->integer('price_cents')->nullable();

            // Evitar duplicados
            $table->unique(['course_id', 'user_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_user');
    }
};
