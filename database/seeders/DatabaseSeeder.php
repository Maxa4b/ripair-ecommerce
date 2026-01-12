<?php

namespace Database\Seeders;

use App\Models\Support\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CatalogSeeder::class,
        ]);

        $admin = User::updateOrCreate(
            ['email' => 'admin@ripair.fr'],
            [
                'first_name' => 'RIPAIR',
                'last_name' => 'Admin',
                'password' => Hash::make('Ripair@2024'),
                'account_type' => 'standard',
                'email_verified_at' => now(),
            ],
        );

        $admin->roles()->sync([Role::where('slug', 'super-admin')->first()->id]);

        $pro = User::updateOrCreate(
            ['email' => 'pro@ripair.fr'],
            [
                'first_name' => 'Atelier',
                'last_name' => 'Pro',
                'company_name' => 'RIPAIR Atelier',
                'account_type' => 'pro',
                'pro_status' => 'approved',
                'can_access_ht_prices' => true,
                'pro_discount_rate' => 7.5,
                'password' => Hash::make('RipairPro@2024'),
            ],
        );
    }
}
