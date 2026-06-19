<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->text('requirement_description')->nullable();
            $table->enum('status', [
                'requirement_submitted', 'admin_review', 'quotation_generated',
                'quotation_sent', 'customer_approved', 'customer_rejected', 'order_created',
            ])->default('requirement_submitted');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bulk_order_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_order_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_type');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bulk_order_id')->constrained()->cascadeOnDelete();
            $table->string('quotation_number')->unique();
            $table->decimal('amount', 12, 2);
            $table->text('details')->nullable();
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->nullableMorphs('payable');
            $table->enum('method', ['razorpay', 'phonepe', 'cod'])->default('razorpay');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('gateway_payment_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('refund_id')->unique();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('transaction_id')->unique();
            $table->enum('type', ['payment', 'refund', 'commission', 'payout']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('quotations');
        Schema::dropIfExists('bulk_order_files');
        Schema::dropIfExists('bulk_orders');
    }
};
