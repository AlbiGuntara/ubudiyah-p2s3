<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    private const PERMISSIONS = ['view_iksass', 'create_iksass', 'edit_iksass', 'delete_iksass'];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Existing installs already have roles; grant the new permissions to
        // them so the master data menu is usable without re-seeding.
        $roles = [
            'super_admin' => self::PERMISSIONS,
            'petugas' => ['view_iksass'],
            'pembina' => ['view_iksass'],
        ];

        foreach ($roles as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if (! $role) {
                continue;
            }

            $role->syncPermissions(
                $role->getPermissionNames()->merge($permissions)->unique()->values()->all()
            );
        }
    }

    public function down(): void
    {
        Permission::whereIn('name', self::PERMISSIONS)->delete();
    }
};
