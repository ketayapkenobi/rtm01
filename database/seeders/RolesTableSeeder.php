<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'Project Manager', 'guard_name' => 'web']);
        Role::create(['name' => 'Business Analyst', 'guard_name' => 'web']);
        Role::create(['name' => 'Software Tester', 'guard_name' => 'web']);
        Role::create(['name' => 'Client', 'guard_name' => 'web']);
    }
}
