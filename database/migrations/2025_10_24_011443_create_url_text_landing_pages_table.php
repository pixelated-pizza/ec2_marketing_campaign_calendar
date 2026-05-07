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
        Schema::create('url_text_landing_pages', function (Blueprint $table) {
            $table->uuid()->primary();
            $table->string('wsd_id');
            $table->string('landing_page_url')->nullable();

            $table->foreign('wsd_id')->references('wsd_id')->on('website_sale_details')->onDelete('cascade');   

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('url_text_landing_pages');
    }
};
