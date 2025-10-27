<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // En tu BD todo está en camelCase, mantenemos ese estilo
            if (!Schema::hasColumn('users', 'province')) {
                $table->string('province', 255)->nullable();
            }
            if (!Schema::hasColumn('users', 'birthDate')) {
                $table->date('birthDate')->nullable();
            }
            if (!Schema::hasColumn('users', 'marketingConsent')) {
                $table->boolean('marketingConsent')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'province')) {
                $table->dropColumn('province');
            }
            if (Schema::hasColumn('users', 'birthDate')) {
                $table->dropColumn('birthDate');
            }
            if (Schema::hasColumn('users', 'marketingConsent')) {
                $table->dropColumn('marketingConsent');
            }
        });
    }
};
