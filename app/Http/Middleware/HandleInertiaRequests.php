<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function share(Request $request): array
    {
        $user = $request->user();
        $appearance = $request->session()->get('appearance', 'light');

        if ($request->hasCookie('appearance')) {
            $appearance = $request->cookie('appearance');
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => $user->role,
                    'foto' => $user->foto,
                    'jabatan' => $user->jabatan,
                    'tugas' => $user->tugas,
                    'is_super_admin' => $user->isSuperAdmin(),
                    'is_petugas' => $user->isPetugas(),
                    'is_pembina' => $user->isPembina(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                ] : null,
            ],
            'appearance' => $appearance,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'warning' => fn () => $request->session()->get('warning'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
