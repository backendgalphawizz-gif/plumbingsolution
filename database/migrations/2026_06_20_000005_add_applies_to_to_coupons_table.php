<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->enum('applies_to', ['order', 'booking'])->default('order')->after('code');
        });

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['code', 'applies_to']);
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropUnique(['code', 'applies_to']);
            $table->unique(['code']);
            $table->dropColumn('applies_to');
        });
    }
};
