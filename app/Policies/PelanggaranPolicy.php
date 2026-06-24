<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Pelanggaran;

class PelanggaranPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_pelanggaran');
    }

    public function view(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('view_pelanggaran');
    }

    public function create(User $user): bool
    {
        return $user->can('create_pelanggaran');
    }

    public function update(User $user, Pelanggaran $pelanggaran): bool
    {
        // Petugas can only edit their own violations
        if ($user->isPetugas()) {
            $petugas = \App\Models\Petugas::where('user_id', $user->id)->first();
            return $petugas && $pelanggaran->petugas_id === $petugas->id;
        }
        return $user->can('edit_pelanggaran');
    }

    public function delete(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('delete_pelanggaran');
    }
}
