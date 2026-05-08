<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
class SuperAdminAccount extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $editor_id = Roles::get()->where('role_name', 'Administrator')->value('role_id');

         DB::table('users')->insert([
            'name' => 'ESS Super Admin',
            'role_id' => $editor_id,
            'email' => 'millstrading.ralph.r@gmail.com',
            'password' => Hash::make('Windy19dirt!!')
        ]);
    }
}
