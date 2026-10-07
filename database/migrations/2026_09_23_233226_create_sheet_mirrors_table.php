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
        Schema::create('sheet_mirrors', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();        
            $table->string('spreadsheet_id');
            $table->string('sheet_name')->default('Sheet1');
            $table->json('headers')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sheet_mirrors');
    }
};
