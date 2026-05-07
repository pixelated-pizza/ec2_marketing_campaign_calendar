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
        Schema::create('website_sale_details', function (Blueprint $table) {
            $table->uuid('wsd_id')->primary();
            $table->uuid('wc_id');
            $table->string('terms_conditions')->nullable();
            $table->string('mockup_banner_locations')->nullable();
            $table->string('event_master_sheet_url')->nullable();
            $table->boolean('is_sku_list_to_feature')->default(false);
            $table->string('ess')->default('Tonni & Ralph');
            $table->string('cms_to_audit')->nullable();
            $table->foreign('wc_id')
                  ->references('wc_id')
                  ->on('website_campaigns')
                  ->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_sale_details');
    }
};
