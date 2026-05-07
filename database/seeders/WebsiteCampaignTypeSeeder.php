<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WebsiteCampaignTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            "Adhoc Promos/Coupons",
            "Website Sale",
        ];

        foreach ($types as $typeName) {
            DB::table('website_campaign_types')->insert([
                'campaign_type_id' => Str::uuid(),
                'campaign_type_name' => $typeName,
            ]);
        }
    }
}
