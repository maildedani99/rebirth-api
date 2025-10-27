<?php
// database/migrations/2025_10_01_130000_drop_stripe_keys_from_configs.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_es_publishable_key',
                'stripe_es_secret_key',
                'stripe_es_webhook_secret',
                'stripe_us_publishable_key',
                'stripe_us_secret_key',
                'stripe_us_webhook_secret',
            ]);
        });
    }

    public function down(): void {
        Schema::table('configs', function (Blueprint $table) {
            $table->string('stripe_es_publishable_key')->nullable();
            $table->string('stripe_es_secret_key')->nullable();
            $table->string('stripe_es_webhook_secret')->nullable();
            $table->string('stripe_us_publishable_key')->nullable();
            $table->string('stripe_us_secret_key')->nullable();
            $table->string('stripe_us_webhook_secret')->nullable();
        });
    }
};
