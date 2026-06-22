<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_sale_details', function (Blueprint $table) {
            $table->uuid('wsd_id')->primary();

            $table->string('event_name')->nullable();
            $table->string('channel_name')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('terms_conditions')->nullable();

            $table->text('mockup_banner_locations')->nullable();
            $table->text('mockup_banner_img')->nullable();

            $table->text('featured_products_sheet_url')->nullable();
            $table->text('event_master_sheet_url')->nullable();
            $table->text('run_sheet_url')->nullable();

            $table->boolean('is_sku_list_to_feature')->default(false);

            $table->text('ess')->nullable();
            $table->text('cms_to_audit')->nullable();
            $table->text('featured_banner_text')->nullable();
            $table->text('sku_in_category_creative')->nullable();
            $table->text('url_text')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sale_details');
    }
};