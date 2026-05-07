<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin_id = Roles::get()->where('role_name', 'Administrator')->value('role_id');

        DB::table('users')->insert([
            'name' => 'Administrator',
            'role_id' => $admin_id,
            'email' => 'millstrading.ralph.r@gmail.com',
            'password' => Hash::make('Windy19dirt!!') 
        ]);
    }
}
