<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('category_featured_skuses', function (Blueprint $table) {
            $table->uuid('sku_featured_id')->primary();
            $table->string('category_name')->nullable();
            $table->string('block_id')->nullable();
            $table->string('block_name')->nullable();
            $table->string('identifier')->nullable();
            $table->string('type')->nullable();
            $table->string('website')->nullable();
            $table->string('product_landing_page')->nullable();
            $table->integer('qty')->default(0);
            $table->decimal('rrp', 10, 2)->nullable();
            $table->decimal('website_price', 10, 2)->nullable();
            $table->decimal('special_price', 10, 2)->nullable();
            $table->string('landing_page')->nullable();
            $table->string('creative_location')->nullable();
            $table->string('price_change')->nullable();
            $table->string('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_featured_skuses');
    }
};
