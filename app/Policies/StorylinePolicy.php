<?php

namespace Pickems\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;

class StorylinePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
    }

    public function before($user, $ability)
    {
        return $user->role == 'admin';
    }
}
