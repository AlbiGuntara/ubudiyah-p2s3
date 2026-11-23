<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Daerah
            'view_daerah', 'create_daerah', 'edit_daerah', 'delete_daerah',
            // IKSASS
            'view_iksass', 'create_iksass', 'edit_iksass', 'delete_iksass',
            // Asrama
            'view_asrama', 'create_asrama', 'edit_asrama', 'delete_asrama',
            // Santri
            'view_santri', 'create_santri', 'edit_santri', 'delete_santri', 'import_santri',
            // Daftar Pelanggaran
            'view_daftar_pelanggaran', 'create_daftar_pelanggaran', 'edit_daftar_pelanggaran', 'delete_daftar_pelanggaran',
            // Pelanggaran
            'view_pelanggaran', 'create_pelanggaran', 'edit_pelanggaran', 'delete_pelanggaran',
            // Pembinaan
            'view_pembinaan', 'create_pembinaan', 'edit_pembinaan', 'delete_pembinaan',
            // Surat Panggilan
            'cetak_surat_panggilan',
            // Laporan
            'view_laporan', 'export_laporan',
            // Dashboard
            'view_dashboard',
            // Audit
            'view_audit',
            // User management
            'manage_users',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create roles and assign permissions
        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->syncPermissions(Permission::all());

        $petugas = Role::findOrCreate('petugas', 'web');
        $petugas->syncPermissions([
            'view_dashboard',
            'view_santri',
            'view_asrama',
            'view_daerah',
            'view_daftar_pelanggaran',
            'view_pelanggaran', 'create_pelanggaran', 'edit_pelanggaran',
            'cetak_surat_panggilan',
            'view_pembinaan',
            'view_laporan',
        ]);

        $pembina = Role::findOrCreate('pembina', 'web');
        $pembina->syncPermissions([
            'view_dashboard',
            'view_santri',
            'view_asrama',
            'view_daerah',
            'view_pelanggaran',
            'view_pembinaan', 'create_pembinaan', 'edit_pembinaan', 'delete_pembinaan',
            'view_laporan',
        ]);
    }
}
