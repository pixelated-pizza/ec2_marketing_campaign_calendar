<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SheetMirror;

class SheetMirrorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        SheetMirror::create([
            'key' => 'website-sale-details',
            'spreadsheet_id' => env('WSD_SPREADSHEET_ID'),
            'sheet_name' => 'Sheet1',
        ]);
    }
}
