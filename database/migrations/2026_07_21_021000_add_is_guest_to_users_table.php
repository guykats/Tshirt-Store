<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks a User row created automatically for a guest checkout (see
     * CheckoutController::store) rather than a real self-registered
     * account. The row has an unusable random password and was never
     * shown to anyone, but is otherwise a completely normal User so every
     * existing user_id-based order-ownership check (OrderPolicy,
     * OrderController, InvoiceService, OrderConfirmationMail, the "my
     * orders" listing) keeps working unmodified.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_guest')->default(false)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_guest');
        });
    }
};
