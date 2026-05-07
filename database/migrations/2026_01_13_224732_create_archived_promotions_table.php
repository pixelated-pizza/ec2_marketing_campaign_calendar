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
        Schema::create('archived_promotions', function (Blueprint $table) {
            $table->uuid('promo_id')->primary();
            $table->string('promo_name')->nullable();
            $table->string('description')->nullable();
            $table->boolean('does_include_parts')->default(false);
            $table->boolean('does_include_marketplace_products')->default(false);
            $table->string('creatives')->nullable();
            $table->string('coupon_label')->nullable();
            $table->string('coupon_code')->nullable();
            $table->uuid('website_store')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('ENDED');
            $table->string('updated_at')->default(now());

            $table->foreign('website_store')->references('store_id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_promotions');
    }
};
