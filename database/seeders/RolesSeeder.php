<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            "Viewer",
            "Editor",
            "Administrator"
        ];

        foreach ($roles as $name) {
            DB::table('roles')->insert([
                'role_id' => Str::uuid(),
                'role_name' => $name,
            ]);
        }
    }
}
