<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'userID' => '10001',
            'role_id' => 1,
        ]);

        $this->call(RolesTableSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(PrioritySeeder::class);
        $this->call(StatusSeeder::class);
    }
}
