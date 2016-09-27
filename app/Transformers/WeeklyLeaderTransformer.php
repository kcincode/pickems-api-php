<?php
namespace Pickems\Transformers;

use League\Fractal;
use Pickems\Models\WeeklyLeader;

class WeeklyLeaderTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = ['team'];

    public function transform(WeeklyLeader $weeklyLeader)
    {
        return [
            'id' => (int) $weeklyLeader->id,
            'week' => (int) $weeklyLeader->week,
            'points' => (int) $weeklyLeader->points,
        ];
    }

    public function includeTeam(WeeklyLeader $weeklyLeader)
    {
        return $this->item($weeklyLeader->team, new TeamTransformer, 'teams');
    }
}
