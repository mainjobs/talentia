<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'reclutador']);

        $admin1 = User::firstOrCreate(
            ['email' => 'ralcantara@grupomainjobs.com'],
            ['name' => 'Rafa', 'password' => bcrypt('password')]
        );

        $admin1->assignRole('admin');

        $admin2 = User::firstOrCreate(
            ['email' => 'daveloza@grupomainjobs.com'],
            ['name' => 'Deiver', 'password' => bcrypt('password')]
        );

        $admin2->assignRole('admin');
    }
}
