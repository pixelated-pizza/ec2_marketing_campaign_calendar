<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryChannelColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $channels = [
        //     "Mytopia"                  => "#16a34a", // green
        //     "Edisons"                  => "#2563eb", // blue
        //     "Hot Deals"                => "#f59e0b", // orange
        //     "Additional Campaigns"     => "#dc2626", // red
        //     "Adhoc Promos/Coupons"     => "#9333ea", // purple
        //     "External Events"          => "#0d9488", // teal
        //     "eBay Retail Trade Calendar" => "#ca8a04", // yellow
        //     "eBay"                     => "#1d4ed8", // indigo
        //     "Coles"                    => "#b91c1c", // dark red
        //     "Catch"                    => "#ea580c", // deep orange
        //     "Kogan"                    => "#991b1b", // dark maroon
        //     "MyDeal/WMP"               => "#047857", // emerald
        //     "Harvey Norman"            => "#0369a1", // cyan
        //     "Everyday Market"          => "#7c3aed", // violet
        //     "Lasoo"                    => "#059669", // green
        //     "Bunnings"                 => "#065f46", // deep green
        //     "Amazon"                   => "#f97316", // orange
        //     "Chemist Warehouse"        => "#e11d48", // rose
        // ];

        // foreach ($channels as $name => $color) {
        //     DB::table('category_channels')
        //         ->where('name', $name)
        //         ->update(['color' => $color]);
        // }
    }
}
