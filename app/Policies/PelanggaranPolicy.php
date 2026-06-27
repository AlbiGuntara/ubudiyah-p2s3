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
        return $user->can('edit_pelanggaran');
    }

    public function delete(User $user, Pelanggaran $pelanggaran): bool
    {
        return $user->can('delete_pelanggaran');
    }
}
