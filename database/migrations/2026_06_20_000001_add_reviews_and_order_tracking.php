<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_number')->nullable()->after('cancellation_reason');
            $table->string('courier_name')->nullable()->after('tracking_number');
            $table->string('invoice_path')->nullable()->after('courier_name');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tracking_number', 'courier_name', 'invoice_path']);
        });

        Schema::dropIfExists('product_reviews');
    }
};
