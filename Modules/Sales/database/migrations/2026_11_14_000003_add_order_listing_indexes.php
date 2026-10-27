<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'status'], 'orders_store_status_index');
            $table->index(['store_id', 'customer_id'], 'orders_store_customer_index');
            $table->index(['store_id', 'created_at'], 'orders_store_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_status_index');
            $table->dropIndex('orders_store_customer_index');
            $table->dropIndex('orders_store_created_index');
        });
    }
};
