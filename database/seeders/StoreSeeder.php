<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $stores = [
            "Website - Edisons",
            "Website - Mytopia",
            
        ];

        foreach ($stores as $name) {
            DB::table('stores')->insert([
                'store_id' => Str::uuid(),
                'store_name' => $name,
            ]);
        }
    }
}
