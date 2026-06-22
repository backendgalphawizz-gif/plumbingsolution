<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->string('account_number')->nullable()->after('service_area');
            $table->string('account_holder_name')->nullable()->after('account_number');
            $table->string('ifsc_code')->nullable()->after('account_holder_name');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->enum('account_type', ['savings', 'current'])->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('service_providers', function (Blueprint $table) {
            $table->dropColumn([
                'account_number',
                'account_holder_name',
                'ifsc_code',
                'bank_name',
                'account_type',
            ]);
        });
    }
};
