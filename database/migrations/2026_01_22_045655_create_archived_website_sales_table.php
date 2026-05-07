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
        Schema::create('archived_website_sales', function (Blueprint $table) {
            $table->uuid('ws_archive_id')->primary();
            $table->string('wc_id');

            $table->foreign('wc_id')->references('wc_id')->on('website_campaigns')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_website_sales');
    }
};
