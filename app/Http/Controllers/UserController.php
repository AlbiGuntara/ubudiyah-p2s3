<?php
namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort_column', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        $allowedSorts = ['name', 'username', 'email', 'jabatan', 'created_at'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest();
        }

        $users = $query->paginate((int) $request->input("per_page", 10));

        $roles = Role::all();

        return Inertia::render('petugas/index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        }

        $data['password'] = bcrypt($data['password']);

        $user = User::create($data);

        if ($request->filled('role')) {
            $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web']);
            $user->assignRole($role);
        }

        return redirect()->back()->with('success', 'Petugas berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
            $data['foto'] = $request->file('foto')->store('petugas', 'public');
        } else {
            unset($data['foto']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);

        if ($request->filled('role')) {
            $role = Role::firstOrCreate(['name' => $request->role, 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }

        return redirect()->back()->with('success', 'Petugas berhasil diubah.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->isSuperAdmin() && User::where('role', 'super_admin')->count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus super admin terakhir.');
        }

        if ($user->foto) {
            Storage::disk('public')->delete($user->foto);
        }

        $user->delete();

        return redirect()->back()->with('success', 'Petugas berhasil dihapus.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $ids = $request->input('ids', []);
        $superAdminCount = User::whereIn('id', $ids)->where('role', 'super_admin')->count();
        $remainingSuperAdmins = User::where('role', 'super_admin')->whereNotIn('id', $ids)->count();

        if ($superAdminCount > 0 && $remainingSuperAdmins === 0) {
            return back()->with('error', 'Tidak dapat menghapus semua super admin.');
        }

        $users = User::whereIn('id', $ids)->get();
        foreach ($users as $user) {
            if ($user->foto) {
                Storage::disk('public')->delete($user->foto);
            }
        }

        User::whereIn('id', $ids)->delete();

        AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'aktivitas' => 'menghapus banyak Petugas',
            'model_type' => User::class,
            'data' => ['ids' => $ids, 'count' => count($ids)],
        ]);

        return redirect()->back()->with('success', 'Petugas berhasil dihapus.');
    }
}
