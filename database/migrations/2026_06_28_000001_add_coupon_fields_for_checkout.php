<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('applied_order_coupon_code', 30)->nullable()->after('wallet_balance');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 30)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('applied_order_coupon_code');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('coupon_code');
        });
    }
};
