<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Normalizamos primero los datos existentes a booleano
        // antes de cambiar el tipo de columna
        DB::table('users')->update([
            'depositStatus' => DB::raw("
                CASE
                    WHEN depositStatus IN ('1', 'true', 'paid', 'completed', 'yes', 'ok') THEN 1
                    ELSE 0
                END
            "),
            'finalPayment' => DB::raw("
                CASE
                    WHEN finalPayment IN ('1', 'true', 'paid', 'completed', 'yes', 'ok') THEN 1
                    ELSE 0
                END
            ")
        ]);

        // Evitamos depender de doctrine/dbal usando SQL nativo
        DB::statement("ALTER TABLE users MODIFY depositStatus TINYINT(1) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE users MODIFY finalPayment TINYINT(1) NOT NULL DEFAULT 0");
    }

    public function down(): void
    {
        // Si alguna vez se revierte, devolvemos el tipo string
        DB::statement("ALTER TABLE users MODIFY depositStatus VARCHAR(20) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE users MODIFY finalPayment VARCHAR(20) NOT NULL DEFAULT 'pending'");
    }
};
