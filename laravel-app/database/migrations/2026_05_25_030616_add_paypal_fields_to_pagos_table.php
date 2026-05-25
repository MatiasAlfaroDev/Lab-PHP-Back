<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->string('paypal_order_id')->nullable()->after('estado');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropColumn(['paypal_order_id', 'paypal_capture_id']);
        });
    }
};
