<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('was_price', 12, 2)->nullable();
            $table->string('tag', 50)->nullable();
            $table->string('brand', 120)->nullable();
            $table->string('leather_type', 100)->nullable();
            $table->string('hardware', 100)->nullable();
            $table->string('dimensions', 100)->nullable();
            // Expression defaults: MySQL rejects plain literal defaults on JSON columns.
            $table->jsonb('colors')->default(new Expression("('[]')"));
            $table->string('glyph', 10)->default('👜');
            $table->string('image_url', 500)->nullable();
            $table->jsonb('images')->default(new Expression("('[]')"));
            $table->jsonb('links')->default(new Expression("('{}')"));
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->integer('review_count')->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
