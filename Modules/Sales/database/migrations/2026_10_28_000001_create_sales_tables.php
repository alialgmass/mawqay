<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_token', 64)->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'customer_id']);
            $table->unique(['store_id', 'guest_token']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('price_when_added');
            $table->timestamps();

            $table->unique(['cart_id', 'variant_id']);
        });

        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('governorate')->nullable();
            $table->unsignedBigInteger('fee');
            $table->timestamps();

            $table->index(['store_id', 'kind']);
        });

        Schema::create('order_counters', function (Blueprint $table) {
            $table->foreignId('store_id')->primary()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('last_number')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('number');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('pending_payment');
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('shipping_fee');
            $table->unsignedBigInteger('total');
            $table->string('currency', 3)->default('EGP');
            $table->string('payment_method');
            $table->json('shipping_address');
            $table->json('customer_snapshot');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'number']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('product_variants')->restrictOnDelete();
            $table->string('product_name');
            $table->string('sku');
            $table->string('size');
            $table->string('color');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->decimal('commission_rate', 5, 2);
            $table->unsignedBigInteger('commission_amount');
            $table->timestamps();
        });

        Schema::create('order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->string('actor_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_logs');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_counters');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
