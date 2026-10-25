<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('account_name');
            $table->string('account_number');
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('method');
            $table->unsignedBigInteger('amount');
            $table->string('status')->default('pending_review');
            $table->unsignedTinyInteger('attempt');
            $table->string('receipt_path');
            $table->string('receipt_mime', 50);
            $table->string('transfer_reference')->nullable();
            $table->string('sender_phone', 20)->nullable();
            $table->json('method_snapshot')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('duplicate_of_id')->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'attempt']);
            $table->index(['status', 'created_at']);
        });

        // Platform-level: only a reference's fingerprint and the first Payment that used it,
        // so Stores can be compared without any Store reading another Store's rows.
        Schema::create('payment_reference_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->unsignedBigInteger('first_payment_id');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reference_fingerprints');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('payment_methods');
    }
};
