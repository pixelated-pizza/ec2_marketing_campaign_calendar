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
        Schema::table('website_sale_details', function (Blueprint $table) {
            $table->string('sku_in_category_creative')->nullable();
            $table->string('featured_banner_text')->nullable();
            $table->string('url_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_sale_details', function (Blueprint $table) {
            $table->dropColumn('sku_in_category_creative');
            $table->dropColumn('featured_banner_text');
            $table->dropColumn('url_text');
        });
    }
};
