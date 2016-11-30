<?php

namespace Pickems\Policies;

use Pickems\Models\User;
use Pickems\Models\Team;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
    }

    public function patch(User $user, Team $team)
    {
        return $user->role == 'admin' || $user->id === $team->user->id;
    }

    public function put(User $user, Team $team)
    {
        return $user->role == 'admin' || $user->id === $team->user->id;
    }

    public function delete(User $user, Team $team)
    {
        return $user->role == 'admin' || $user->id === $team->user->id;
    }

    public function admin(User $user, Team $team)
    {
        return $user->role == 'admin';
    }
}
