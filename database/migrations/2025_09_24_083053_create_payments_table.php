<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users');   // usuario cliente
            $table->foreignId('course_id')->nullable()->constrained(); // si tienes tabla courses
            $table->integer('amount_cents');                        // importe en céntimos
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['pending','paid','failed','refunded'])->default('pending');
            $table->string('method')->nullable();   // card, transfer, cash...
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
