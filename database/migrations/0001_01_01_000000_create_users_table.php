<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identidad básica
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email')->unique();
            $table->string('password');

            // Datos opcionales
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('dni')->unique(); // si no quieres unique, quítalo
            $table->string('postalCode')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();

            // Rol y estado
            $table->enum('role', ['admin', 'teacher', 'client'])->default('client');
            $table->boolean('isActive')->default(false);
            $table->string('status')->default('pending');

            // Curso y tutor
            $table->unsignedInteger('coursePriceCents')->default(0);
            $table->foreignId('tutor_id')->nullable()->constrained('users')->nullOnDelete();

            // Contrato / pagos (opcional)
            $table->string('depositStatus')->default('pending');
            $table->string('finalPayment')->default('pending');
            $table->boolean('contractSigned')->default(false);
            $table->timestamp('contractDate')->nullable();
            $table->string('contractIp')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
