<?php
// database/migrations/2025_10_01_000000_create_configs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('configs', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_course', 10, 2)->nullable();
            $table->decimal('price_session', 10, 2)->nullable();
            $table->decimal('price_booking', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('configs');
    }
};
