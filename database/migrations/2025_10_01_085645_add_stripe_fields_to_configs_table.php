<?php
// database/migrations/2025_10_01_120000_add_stripe_fields_to_configs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('configs', function (Blueprint $table) {
            // Stripe ES
            $table->string('stripe_es_publishable_key')->nullable()->after('price_booking');
            $table->string('stripe_es_secret_key')->nullable()->after('stripe_es_publishable_key');
            $table->string('stripe_es_webhook_secret')->nullable()->after('stripe_es_secret_key');

            // Stripe US
            $table->string('stripe_us_publishable_key')->nullable()->after('stripe_es_webhook_secret');
            $table->string('stripe_us_secret_key')->nullable()->after('stripe_us_publishable_key');
            $table->string('stripe_us_webhook_secret')->nullable()->after('stripe_us_secret_key');

            // Predeterminado: 'es' o 'us'
            $table->enum('stripe_default_region', ['es', 'us'])->default('es')->after('stripe_us_webhook_secret');
        });
    }

    public function down(): void {
        Schema::table('configs', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_es_publishable_key',
                'stripe_es_secret_key',
                'stripe_es_webhook_secret',
                'stripe_us_publishable_key',
                'stripe_us_secret_key',
                'stripe_us_webhook_secret',
                'stripe_default_region',
            ]);
        });
    }
};

