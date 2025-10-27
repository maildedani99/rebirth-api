<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('courses')) {
            // La tabla ya existe → asegura columnas que necesitamos
            Schema::table('courses', function (Blueprint $table) {
                if (!Schema::hasColumn('courses', 'price')) {
                    $table->decimal('price', 10, 2)->default(0)->after('description');
                }
                if (!Schema::hasColumn('courses', 'content')) {
                    // Si tu MySQL no soporta JSON, usa longText()
                    $table->json('content')->nullable()->after('price');
                }
            });
            return;
        }

        // Creación normal si no existe
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->json('content')->nullable();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};


