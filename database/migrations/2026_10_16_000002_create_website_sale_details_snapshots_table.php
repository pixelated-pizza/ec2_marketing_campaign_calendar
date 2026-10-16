<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_sale_details_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('source_filename')->nullable();
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('column_count')->default(0);
            $table->longText('workbook_data');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_sale_details_snapshots');
    }
};
