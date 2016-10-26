<?php

namespace Pickems\Providers;

use Pickems\Models\User;
use Pickems\Models\Team;
use Pickems\Models\Storyline;
use Pickems\Policies\UserPolicy;
use Pickems\Policies\TeamPolicy;
use Pickems\Policies\StorylinePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Team::class => TeamPolicy::class,
        Storyline::class => StorylinePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot()
    {
        $this->registerPolicies();
    }
}
