<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('base_price');
            $table->unsignedBigInteger('sale_price')->nullable();
            $table->string('status')->default('draft');
            $table->text('hide_reason')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'slug']);
            $table->index(['store_id', 'status']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('size');
            $table->string('color');
            $table->unsignedBigInteger('price_override')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'sku']);
            $table->unique(['product_id', 'size', 'color']);
        });

        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
