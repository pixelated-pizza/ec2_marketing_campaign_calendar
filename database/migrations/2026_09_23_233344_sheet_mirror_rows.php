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
        Schema::create('sheet_mirror_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_mirror_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_index'); 
            $table->json('data');                
            $table->timestamps();

            $table->index(['sheet_mirror_id', 'row_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
