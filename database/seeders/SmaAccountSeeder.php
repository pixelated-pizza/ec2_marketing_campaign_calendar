<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;
class SmaAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $editor_id = Roles::get()->where('role_name', 'Editor')->value('role_id');

         DB::table('users')->insert([
            'name' => 'Ivy Tusoy',
            'role_id' => $editor_id,
            'email' => 'millstrading.ivy.tusoy@gmail.com',
            'password' => Hash::make('PrimeWater19!!')
        ]);
    }
}
