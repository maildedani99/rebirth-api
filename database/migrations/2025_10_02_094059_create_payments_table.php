<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
{
    if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
        // ya existe, no hacemos nada para evitar el error 1050
        return;
    }

    Schema::create('payments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('course_id')->nullable();
        $table->unsignedInteger('amount_cents');
        $table->string('currency', 3)->default('EUR');
        $table->string('status', 20)->default('pending');
        $table->string('method', 50)->nullable();
        $table->timestamp('paid_at')->nullable();
        $table->string('reference', 100)->nullable();
        $table->text('notes')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamps();
    });
}


    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
