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
            $table->string('run_sheet_url')->nullable()->after('event_master_sheet_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_sale_details', function (Blueprint $table) {
            $table->dropColumn('run_sheet_url');
        });
    }
};
