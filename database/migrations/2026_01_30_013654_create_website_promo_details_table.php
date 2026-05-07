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
        Schema::create('website_promo_details', function (Blueprint $table) {
            $table->char('promo_id', 36)->primary();
            $table->string('wc_id', 255)->nullable(); // no ->after()
            $table->string('promo_name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->string('terms_and_conditions', 255)->nullable();
            $table->tinyInteger('does_include_parts')->default(0);
            $table->tinyInteger('does_include_marketplace_products')->default(0);
            $table->string('creatives', 255)->nullable();
            $table->string('coupon_label', 255)->nullable();
            $table->string('coupon_code', 255)->nullable();
            $table->char('website_store', 36)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 255)->default('ENDED');
            $table->string('updated_at', 255)->nullable();


            $table->foreign('wc_id')->references('wc_id')->on('website_campaigns')->onDelete('cascade');
            $table->foreign('website_store')->references('store_id')->on('stores')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_promo_details');
    }
};
