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
            $table->uuid('archived_promo_id')->primary();
            $table->string('wc_id');


            $table->foreign('wc_id')->references('wc_id')->on('website_campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archived_promotions', function (Blueprint $table) {
            $table->dropForeign(['wc_id']);
            $table->dropColumn('wc_id');
        });
    }
};
