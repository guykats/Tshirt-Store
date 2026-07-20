<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Only an admin can remove a review — moderation of abusive/fake content,
     * not something the author or any other customer can do themselves.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }
}
