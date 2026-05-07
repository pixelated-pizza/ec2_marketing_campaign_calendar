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
        Schema::table('archived_promotions', function (Blueprint $table) {
            $table->string('promo_id');


            $table->foreign('promo_id')->references('promo_id')->on('website_promo_details')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archived_promotions', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropColumn('promo_id');
        });
    }
};
