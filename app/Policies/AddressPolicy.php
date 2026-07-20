<?php

namespace App\Policies;

use App\Models\Address;
use App\Models\User;

class AddressPolicy
{
    /**
     * An address book entry is only ever visible/editable by the customer it
     * belongs to — there is no admin/staff use case for reading or changing
     * another customer's saved address, unlike orders or reviews.
     */
    public function view(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    public function update(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->id === $address->user_id;
    }
}
