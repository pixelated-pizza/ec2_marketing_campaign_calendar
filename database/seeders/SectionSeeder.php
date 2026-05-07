<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $sections = [
            "Homepage Banner",
            "Landing Page Banner"
        ];

        foreach ($sections as $name) {
            DB::table('sections')->insert([
                'section_id' => Str::uuid(),
                'name' => $name,
            ]);
        }
    }
}
