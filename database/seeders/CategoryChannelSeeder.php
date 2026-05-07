<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class CategoryChannelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $channels = [
            "Mytopia",
            "Edisons",
            "Additional Campaigns",
            "Adhoc Promos/Coupons",
            "External Events",
            "eBay Retail Trade Calendar",
            "eBay",
            "Coles",
            "Catch",
            "Kogan",
            "MyDeal/WMP",
            "Harvey Norman",
            "Everyday Market",
            "Lasoo",
            "Bunnings",
            "Amazon",
            "Chemist Warehouse",
        ];

        foreach ($channels as $name) {
            DB::table('category_channels')->insert([
                'channel_id' => Str::uuid(),
                'name' => $name,
            ]);
        }
    }
}
