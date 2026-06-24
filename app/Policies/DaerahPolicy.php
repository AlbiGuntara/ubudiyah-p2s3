<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Daerah;

class DaerahPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_daerah');
    }

    public function view(User $user, Daerah $daerah): bool
    {
        return $user->can('view_daerah');
    }

    public function create(User $user): bool
    {
        return $user->can('create_daerah');
    }

    public function update(User $user, Daerah $daerah): bool
    {
        return $user->can('edit_daerah');
    }

    public function delete(User $user, Daerah $daerah): bool
    {
        return $user->can('delete_daerah');
    }
}
