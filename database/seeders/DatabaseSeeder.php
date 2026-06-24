<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'email' => 'superadmin@pondok.dev',
            'password' => bcrypt('password'),
            'role' => 'super_admin',
        ]);
        $superAdmin->assignRole('super_admin');

        // Create Petugas
        $petugasUser = User::create([
            'name' => 'Petugas Ubudiyah',
            'username' => 'petugas',
            'email' => 'petugas@pondok.dev',
            'password' => bcrypt('password'),
            'role' => 'petugas',
        ]);
        $petugasUser->assignRole('petugas');

        // Create Pembina
        $pembinaUser = User::create([
            'name' => 'Pembina Ubudiyah',
            'username' => 'pembina',
            'email' => 'pembina@pondok.dev',
            'password' => bcrypt('password'),
            'role' => 'pembina',
        ]);
        $pembinaUser->assignRole('pembina');

        $this->call([
            DaerahSeeder::class,
            AsramaSeeder::class,
            SantriSeeder::class,
            PetugasSeeder::class,
            DaftarPelanggaranSeeder::class,
            PelanggaranSeeder::class,
            PembinaanSeeder::class,
        ]);
    }
}
