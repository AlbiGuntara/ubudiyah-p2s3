<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Daerah
            'view_daerah', 'create_daerah', 'edit_daerah', 'delete_daerah',
            // Asrama
            'view_asrama', 'create_asrama', 'edit_asrama', 'delete_asrama',
            // Santri
            'view_santri', 'create_santri', 'edit_santri', 'delete_santri', 'import_santri',
            // Petugas
            'view_petugas', 'create_petugas', 'edit_petugas', 'delete_petugas',
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
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdmin = Role::create(['name' => 'super_admin']);
        $superAdmin->givePermissionTo(Permission::all());

        $petugas = Role::create(['name' => 'petugas']);
        $petugas->givePermissionTo([
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

        $pembina = Role::create(['name' => 'pembina']);
        $pembina->givePermissionTo([
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
