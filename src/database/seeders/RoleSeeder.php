<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Admin', 'Staff', 'User'] as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
}