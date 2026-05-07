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
            $table->dropForeign(['wc_id']);
            $table->dropColumn('wc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archived_promotions', function (Blueprint $table) {
            $table->string('wc_id')->nullable();

            $table->foreign('wc_id')->references('wc_id')->on('website_campaigns')->onDelete('cascade');
        });
    }
};
