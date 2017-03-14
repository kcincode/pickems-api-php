<?php
namespace Pickems\Transformers;

use Pickems\Models\Team;
use League\Fractal;

class TeamTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = ['user'];

    public function transform(Team $team)
    {
        return [
            'id' => (int) $team->id,
            'name' => $team->name,
            'slug' => $team->slug,
            'paid' => (bool) $team->paid,
            'points' => (int) $team->points,
            'playoffs' => (int) $team->playoffs,
            'playoffs_picks' => (int) $team->playoff_picks,
            'wl' => $team->wl,
        ];
    }

    public function includeUser(Team $team)
    {
        if ($team->user) {
            return $this->item($team->user, new UserTransformer, 'users');
        }

        return null;
    }
}
