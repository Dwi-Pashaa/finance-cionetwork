<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Daftar Role
        $roles = [
            'Admin',
        ];

        foreach ($roles as $value) {
            Role::firstOrCreate(['name' => $value]);
        }

        $permissions = [
            'lihat level',
            'tambah level',
            'edit level',
            'hapus level',
            'lihat user',
            'buat user',
            'ubah user',
            'hapus user',
            'lihat pemasukan',
            'tambah pemasukan',
            'edit pemasukan',
            'hapus pemasukan',
            'tambah pengeluaran',
            'lihat pengeluaran',
            'edit pengeluaran',
            'hapus pengeluaran',
            'lihat kategori keuangan',
            'tambah kategori keuangan',
            'edit kategori keuangan',
            'hapus kategori keuangan',
            'lihat saldo',
            'buat saldo',
            'edit saldo',
            'hapus saldo',
            'Api Management',
            'log history',
            'download PDF',
            'download Excel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->syncPermissions($permissions);
        }
    }
}
