<?php

namespace Pickems\Policies;

use Pickems\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function patch(User $authUser, User $user)
    {
        return $authUser->role == 'admin' || $user->id === $authUser->id;
    }

    public function put(User $authUser, User $user)
    {
        return $authUser->role == 'admin' || $user->id === $authUser->id;
    }

    public function delete(User $authUser, User $user)
    {
        return $authUser->role == 'admin';
    }
}
