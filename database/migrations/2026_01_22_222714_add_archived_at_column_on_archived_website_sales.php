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
        Schema::table('archived_website_sales', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('wc_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('archived_website_sales', function (Blueprint $table) {
            $table->dropColumn('archived_at');
        });
    }
};
