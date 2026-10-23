<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_calendar_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('layout_key', 100)->unique();
            // LONGTEXT allows larger workbooks with styles, merges, and cell data.
            $table->longText('workbook_data');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_calendar_layouts');
    }
};
