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

        Schema::table('users', function (Blueprint $table) {
            // Cambiamos el tipo de columna a boolean
            $table->boolean('depositStatus')->default(0)->change();
            $table->boolean('finalPayment')->default(0)->change();
        });
    }

    public function down(): void
    {
        // Si alguna vez se revierte, podríamos devolverlas a string
        Schema::table('users', function (Blueprint $table) {
            $table->string('depositStatus', 20)->default('pending')->change();
            $table->string('finalPayment', 20)->default('pending')->change();
        });
    }
};
