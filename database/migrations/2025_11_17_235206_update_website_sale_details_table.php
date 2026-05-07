<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('website_sale_details', function (Blueprint $table) {
            $table->text('featured_banner_text')->nullable()->change();
            $table->text('sku_in_category_creative')->nullable()->change();
            $table->text('url_text')->nullable()->change();
            $table->text('terms_conditions')->nullable()->change();
            $table->text('mockup_banner_locations')->nullable()->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
