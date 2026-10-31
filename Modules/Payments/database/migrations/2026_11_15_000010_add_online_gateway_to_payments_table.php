<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->change();
            $table->string('receipt_mime', 50)->nullable()->change();
            $table->string('gateway', 30)->nullable()->after('method');
            $table->string('gateway_reference')->nullable()->unique()->after('gateway');
            $table->string('gateway_transaction_id')->nullable()->after('gateway_reference');
            $table->json('gateway_payload')->nullable()->after('gateway_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropColumn(['gateway', 'gateway_reference', 'gateway_transaction_id', 'gateway_payload']);
        });
    }
};
