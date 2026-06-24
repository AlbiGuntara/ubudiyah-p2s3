<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Santri;

class SantriPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_santri');
    }

    public function view(User $user, Santri $santri): bool
    {
        return $user->can('view_santri');
    }

    public function create(User $user): bool
    {
        return $user->can('create_santri');
    }

    public function update(User $user, Santri $santri): bool
    {
        return $user->can('edit_santri');
    }

    public function delete(User $user, Santri $santri): bool
    {
        return $user->can('delete_santri');
    }
}
