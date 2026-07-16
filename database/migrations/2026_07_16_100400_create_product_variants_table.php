<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('size', ['S', 'M', 'L', 'XL', 'XXL']);
            $table->string('color', 50);
            $table->string('sku', 120)->unique();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'size', 'color'], 'uniq_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
