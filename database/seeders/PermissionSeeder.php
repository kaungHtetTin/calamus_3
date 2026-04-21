<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            ['name' => 'user', 'display_name' => 'User Management'],
            ['name' => 'resources', 'display_name' => 'Resource Management'],
            ['name' => 'financial', 'display_name' => 'Financial Management'],
            ['name' => 'administration', 'display_name' => 'Administration'],
            ['name' => 'course', 'display_name' => 'Course Management'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(['name' => $permission['name']], $permission);
        }
    }
}
