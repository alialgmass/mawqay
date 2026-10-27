<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('reason');
            $table->text('note')->nullable();
            $table->boolean('restocked')->default(false);
            $table->json('restock_items')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('reference')->unique();
            $table->timestamps();

            $table->index(['order_id']);
        });

        Schema::create('order_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->text('reason');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_overrides');
        Schema::dropIfExists('refunds');
    }
};
