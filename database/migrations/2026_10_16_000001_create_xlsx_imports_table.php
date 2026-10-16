<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlsx_imports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('original_filename')->nullable();
            $table->string('sheet_name')->nullable();
            $table->json('headers');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlsx_imports');
    }
};
