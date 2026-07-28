<?php

namespace App\Policies;

use App\Models\DesignSuggestion;
use App\Models\User;

class DesignSuggestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, DesignSuggestion $designSuggestion): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DesignSuggestion $designSuggestion): bool
    {
        return $user->isAdmin();
    }
}
