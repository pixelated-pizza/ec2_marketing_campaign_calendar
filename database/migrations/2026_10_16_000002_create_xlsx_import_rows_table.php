<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xlsx_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('xlsx_import_id')->constrained('xlsx_imports')->cascadeOnDelete();
            $table->unsignedInteger('row_index');
            // JSON array of cell values, aligned with xlsx_imports.headers
            $table->json('data');
            $table->timestamps();

            $table->index(['xlsx_import_id', 'row_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xlsx_import_rows');
    }
};
