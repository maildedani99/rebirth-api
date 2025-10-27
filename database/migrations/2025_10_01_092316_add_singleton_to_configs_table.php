<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->char('singleton', 1)->default('X')->nullable()->after('stripe_default_region');
        });

        // Asegura que haya exactamente una fila
        $count = DB::table('configs')->count();

        if ($count === 0) {
            DB::table('configs')->insert([
                'singleton' => 'X',
                'stripe_default_region' => 'es', // valor por defecto
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $firstId = DB::table('configs')->orderBy('id')->value('id');
            DB::table('configs')->where('id', $firstId)->update(['singleton' => 'X']);

            // Limpia duplicados si hay más de uno
            DB::table('configs')->where('id', '<>', $firstId)->delete();
        }

        Schema::table('configs', function (Blueprint $table) {
            $table->unique('singleton');
        });
    }

    public function down(): void
    {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropUnique(['singleton']);
            $table->dropColumn('singleton');
        });
    }
};
