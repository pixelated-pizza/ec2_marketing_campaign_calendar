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
        Schema::create('website_campaigns', function (Blueprint $table) {
            $table->uuid('wc_id')->primary();

            $table->string('website_campaign_key')->nullable();
            $table->string('name');
            $table->uuid('campaign_type_id');
            $table->uuid('section_id');
            $table->boolean('is_applied_to_both_stores')->default(false);
            $table->uuid('store_id')->nullable();

            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_campaigns');
    }
};