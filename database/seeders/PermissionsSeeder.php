<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'view projects',
            'create project',
            'view project',
            'edit project',
            'delete project',
        ];

        foreach ($permissions as $permissionName) {
            if (!Permission::where('name', $permissionName)->exists()) {
                Permission::create(['name' => $permissionName]);
            }
        }

        // Assign permissions to roles
        $projectManager = Role::where('name', 'Project Manager')->first();
        $businessAnalyst = Role::where('name', 'Business Analyst')->first();
        $softwareTester = Role::where('name', 'Software Tester')->first();
        $client = Role::where('name', 'Client')->first();

        $projectManager->givePermissionTo($permissions);
        $businessAnalyst->givePermissionTo(['view projects', 'view project']);
        $softwareTester->givePermissionTo(['view projects', 'view project']);
        $client->givePermissionTo(['view projects', 'view project']);
    }
}
