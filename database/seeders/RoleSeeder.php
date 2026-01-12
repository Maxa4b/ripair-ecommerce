<?php

namespace Database\Seeders;

use App\Models\Support\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Super Admin', 'slug' => 'super-admin'],
            ['name' => 'Gestionnaire catalogue', 'slug' => 'gestionnaire-catalogue'],
            ['name' => 'Logistique', 'slug' => 'logistique'],
            ['name' => 'SAV', 'slug' => 'sav'],
            ['name' => 'Lecture seule', 'slug' => 'lecture'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
