<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AdminNewAccSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $admin_id = Roles::get()->where('role_name', 'Administrator')->value('role_id');

        DB::table('users')->insert([
            'name' => 'App Admin',
            'role_id' => $admin_id,
            'email' => 'ralph.r@millsbrands.com.au',
            'password' => Hash::make('Sandking458@') 
        ]);
    }
}
