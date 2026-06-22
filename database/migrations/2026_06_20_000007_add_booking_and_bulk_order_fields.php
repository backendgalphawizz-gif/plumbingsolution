<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_bookings', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable()->after('notes');
            $table->timestamp('rescheduled_at')->nullable()->after('scheduled_at');
        });

        Schema::table('bulk_orders', function (Blueprint $table) {
            $table->string('full_name')->nullable()->after('user_id');
            $table->string('mobile', 15)->nullable()->after('full_name');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('responded_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });

        Schema::table('bulk_orders', function (Blueprint $table) {
            $table->dropColumn(['full_name', 'mobile']);
        });

        Schema::table('service_bookings', function (Blueprint $table) {
            $table->dropColumn(['cancellation_reason', 'rescheduled_at']);
        });
    }
};
