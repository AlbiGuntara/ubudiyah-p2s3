<?php
namespace App\Http\Controllers;

use Spatie\Permission\Models\Permission;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{

    public function index(): Response
    {
        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('.', $perm->name)[0] ?? 'general';
        });

        return Inertia::render('users/permissions', [
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->back()->with('success', 'Permission berhasil ditambahkan.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $permission->delete();
        return redirect()->back()->with('success', 'Permission berhasil dihapus.');
    }
}
