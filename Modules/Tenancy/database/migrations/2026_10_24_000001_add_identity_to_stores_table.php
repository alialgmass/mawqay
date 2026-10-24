<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('primary_color', 7)->nullable();
            $table->string('secondary_color', 7)->nullable();
            $table->string('favicon_path')->nullable();
            $table->json('social_links')->nullable();
            $table->text('footer_text')->nullable();
            $table->text('shipping_origin')->nullable();
        });

        Schema::create('store_slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('old_slug')->unique();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_slug_redirects');

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'favicon_path', 'social_links', 'footer_text', 'shipping_origin']);
        });
    }
};
