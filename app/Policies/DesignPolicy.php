<?php

namespace App\Policies;

use App\Models\Design;
use App\Models\User;

class DesignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Design $design): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Design $design): bool
    {
        return $user->isAdmin();
    }
}
