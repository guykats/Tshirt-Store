<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            // Same convention as designs.mockup_url (see App\Models\Design): this repo has no
            // real file-upload infrastructure, so "url" is a plain text field an admin fills
            // in — either a real external image URL or one of the existing DesignArt motif
            // keywords, rendered the same way Product::design->mockup_url already is.
            $table->string('url', 500);
            $table->string('alt_text')->nullable();
            // Optional per-variant-color scoping so a gallery can show only the images that
            // match the shopper's selected color swatch, same signal ProductVariant::color
            // already carries.
            $table->string('color', 50)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
