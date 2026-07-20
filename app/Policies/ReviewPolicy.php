<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * A review's own author can correct a typo or change their mind without
     * going through the create-once unique(product_id, user_id) constraint.
     */
    public function update(User $user, Review $review): bool
    {
        return $user->id === $review->user_id;
    }

    /**
     * Admins can remove any review (moderation of abusive/fake content), and
     * a review's own author can remove it themselves — the two are separate
     * grants, not one rule, so admin moderation of *other people's* reviews
     * stays distinct from a user's standing right to manage their own.
     */
    public function delete(User $user, Review $review): bool
    {
        return $user->isAdmin() || $user->id === $review->user_id;
    }
}
