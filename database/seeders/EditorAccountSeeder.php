<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Roles;
use Illuminate\Support\Facades\Hash;

class EditorAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $editor_id = Roles::get()->where('role_name', 'Editor')->value('role_id');

        // DB::table('users')->insert([
        //     'name' => 'Sabina Jaculina',
        //     'role_id' => $editor_id,
        //     'email' => 'millstrading.sabina.jaculina@gmail.com',
        //     'password' => Hash::make('SnowFall18@@')
        // ]);

        
        DB::table('users')->insert([
            'name' => 'Kenn Labra',
            'role_id' => $editor_id,
            'email' => 'millstrading.kenn@gmail.com',
            'password' => Hash::make('Trend19Size!!')
        ]);
    }
}
