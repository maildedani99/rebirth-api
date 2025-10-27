<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Añade SOLO si no existen (usa tus nombres camelCase)
            if (!Schema::hasColumn('users', 'birthDate')) {
                $table->date('birthDate')->nullable();
            }
            if (!Schema::hasColumn('users', 'province')) {
                $table->string('province', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'marketingConsent')) {
                $table->boolean('marketingConsent')->default(false);
            }
            // Si NO tienes isActive o quieres asegurarte de que exista:
            if (!Schema::hasColumn('users', 'isActive')) {
                $table->boolean('isActive')->default(false);
            }
        });

        // Si ya existe isActive pero sin default, ponle default 0 con SQL (no requiere doctrine/dbal)
        // ⚠️ Asegúrate de que el tipo coincide con tu DB (TINYINT(1) en MySQL)
        try {
            DB::statement("ALTER TABLE users MODIFY isActive TINYINT(1) NOT NULL DEFAULT 0");
        } catch (\Throwable $e) {
            // ignora si ya está con ese default
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Elimina solo las que agregamos aquí
            if (Schema::hasColumn('users', 'birthDate')) {
                $table->dropColumn('birthDate');
            }
            if (Schema::hasColumn('users', 'province')) {
                $table->dropColumn('province');
            }
            if (Schema::hasColumn('users', 'marketingConsent')) {
                $table->dropColumn('marketingConsent');
            }
            // no toques isActive si ya existía previamente
        });
    }
};
