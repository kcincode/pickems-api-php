<?php
namespace Pickems\Transformers;

use Pickems\Models\WeeklyLeader;
use League\Fractal;

class WeeklyLeaderTransformer extends Fractal\TransformerAbstract
{

    public function transform(WeeklyLeader $weeklyLeader)
    {
        return [
            'id' => (int) $weeklyLeader->id,
            'week' => (int) $weeklyLeader->week,
            'team_id' => (int) $weeklyLeader->team_id,
            'team' => $weeklyLeader->team,
            'points' => (int) $weeklyLeader->points,
        ];
    }
}
