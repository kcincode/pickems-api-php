<?php
namespace Pickems\Transformers;

use Pickems\Models\User;
use League\Fractal;

class UserTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = ['teams'];

    public function transform(User $user)
    {
        return [
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }

    public function includeTeams(User $user)
    {
        if ($user->teams) {
            return $this->collection($user->teams, new TeamTransformer, 'teams');
        }

        return $this->collection([], new TeamTransformer, 'teams');
    }
}
