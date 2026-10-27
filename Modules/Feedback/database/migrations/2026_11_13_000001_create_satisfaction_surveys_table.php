<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('satisfaction_surveys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->unsignedTinyInteger('overall_rating')->nullable();
            $table->unsignedTinyInteger('product_match_rating')->nullable();
            $table->unsignedTinyInteger('delivery_speed_rating')->nullable();
            $table->text('comment')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('low_rating_flag')->default(false);
            $table->timestamps();

            $table->index(['store_id', 'submitted_at']);
            $table->index(['sent_at', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('satisfaction_surveys');
    }
};
