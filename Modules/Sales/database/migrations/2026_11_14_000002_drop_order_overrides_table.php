<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin overrides now live in the Core audit log.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('order_overrides');
    }

    public function down(): void
    {
        Schema::create('order_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action');
            $table->text('reason');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }
};
