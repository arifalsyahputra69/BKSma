<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat daftar Role sesuai outline
        Role::create(['name' => 'TU/Admin']);
        Role::create(['name' => 'Kepala Sekolah']);
        Role::create(['name' => 'Guru BK']);
        Role::create(['name' => 'Siswa']);
        Role::create(['name' => 'Wali Kelas']);
        Role::create(['name' => 'Guru Mapel']);

        // 2. Buat satu akun Administrator (TU) awal tanpa NIP
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@smakartika.sch.id', // Bisa diganti sesuai kebutuhan
            'password' => bcrypt('admin'),
        ]);
        
        // 3. Assign role TU/Admin ke akun tersebut
        $admin->assignRole('TU/Admin');
    }
}
