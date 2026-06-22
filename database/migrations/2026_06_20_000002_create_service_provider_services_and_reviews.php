<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_provider_service', function (Blueprint $table) {
            $table->foreignId('service_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->primary(['service_provider_id', 'service_id']);
        });

        Schema::create('service_provider_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_booking_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'service_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_provider_reviews');
        Schema::dropIfExists('service_provider_service');
    }
};
