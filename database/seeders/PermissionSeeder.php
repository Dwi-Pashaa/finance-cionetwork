<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
            'lihat pengeluaran',
            'tambah pengeluaran',
            'edit pengeluaran',
            'hapus pengeluaran',
            'lihat saldo',
            'buat saldo',
            'edit saldo',
            'hapus saldo',
            'lihat kategori keuangan',
            'tambah kategori keuangan',
            'edit kategori keuangan',
            'hapus kategori keuangan',
            'Api Management',
            'log history',
            'download PDF',
            'download Excel',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
    }
}
