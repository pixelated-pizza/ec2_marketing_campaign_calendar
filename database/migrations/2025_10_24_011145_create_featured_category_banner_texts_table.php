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
        Schema::create('featured_category_banner_texts', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('wsd_id');
            $table->string('banner_text')->nullable();

            $table->foreign('wsd_id')->references('wsd_id')->on('website_sale_details')->onDelete('cascade');   
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('featured_category_banner_texts');
    }
};
