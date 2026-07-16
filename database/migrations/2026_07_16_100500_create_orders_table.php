<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->enum('status', [
                'pending_approval', 'approved', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded',
            ])->default('pending_approval');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('paypal_order_id')->nullable();
            $table->string('paypal_transaction_id')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid', 'failed', 'refunded'])->default('unpaid');
            $table->foreignId('shipping_address_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('billing_address_id')->constrained('addresses')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
