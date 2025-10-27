<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Añade 'price' si no existe
        if (!Schema::hasColumn('courses', 'price')) {
            Schema::table('courses', function (Blueprint $table) {
                // precio con 2 decimales; ajusta AFTER a tu estructura real si quieres
                $table->decimal('price', 10, 2)->default(0)->after('description');
            });
        }

        // Añade 'content' si no existe
        if (!Schema::hasColumn('courses', 'content')) {
            Schema::table('courses', function (Blueprint $table) {
                // JSON (MySQL 5.7+). Si usas MySQL viejo, cambia a longText()
                $table->json('content')->nullable()->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('courses', 'content')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('content');
            });
        }

        if (Schema::hasColumn('courses', 'price')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
