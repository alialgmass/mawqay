<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);
            $table->string('governorate', 50)->nullable()->unique();
            $table->unsignedInteger('fee');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_shipping_rates');
    }
};
