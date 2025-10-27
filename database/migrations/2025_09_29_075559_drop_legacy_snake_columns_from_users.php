<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $cols = ['first_name','last_name','postal_code','birth_date','is_active','course_price_cents','deposit_status','final_payment','contract_signed','marketing_consent'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // (opcional) recrearlos si quisieras revertir
            if (!Schema::hasColumn('users', 'first_name')) $table->string('first_name')->nullable();
            if (!Schema::hasColumn('users', 'last_name'))  $table->string('last_name')->nullable();
            if (!Schema::hasColumn('users', 'postal_code')) $table->string('postal_code')->nullable();
            if (!Schema::hasColumn('users', 'birth_date')) $table->date('birth_date')->nullable();
            if (!Schema::hasColumn('users', 'is_active')) $table->boolean('is_active')->default(false);
            if (!Schema::hasColumn('users', 'course_price_cents')) $table->unsignedInteger('course_price_cents')->nullable();
            if (!Schema::hasColumn('users', 'deposit_status')) $table->string('deposit_status')->nullable();
            if (!Schema::hasColumn('users', 'final_payment')) $table->string('final_payment')->nullable();
            if (!Schema::hasColumn('users', 'contract_signed')) $table->boolean('contract_signed')->default(false);
            if (!Schema::hasColumn('users', 'marketing_consent')) $table->boolean('marketing_consent')->default(false);
        });
    }
};
