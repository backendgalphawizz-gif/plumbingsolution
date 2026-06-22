<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('email')->nullable()->after('mobile');
            $table->string('business_mobile')->nullable()->after('email');
            $table->string('country')->nullable()->after('address');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->string('pincode', 10)->nullable()->after('city');
            $table->string('shop_logo')->nullable()->after('gst_number');
            $table->string('account_number')->nullable()->after('shop_logo');
            $table->string('account_holder_name')->nullable()->after('account_number');
            $table->string('ifsc_code')->nullable()->after('account_holder_name');
            $table->string('bank_name')->nullable()->after('ifsc_code');
            $table->enum('account_type', ['savings', 'current'])->nullable()->after('bank_name');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'business_mobile',
                'country',
                'state',
                'city',
                'pincode',
                'shop_logo',
                'account_number',
                'account_holder_name',
                'ifsc_code',
                'bank_name',
                'account_type',
            ]);
        });
    }
};
