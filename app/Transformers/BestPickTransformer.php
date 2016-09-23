<?php
namespace Pickems\Transformers;

use Pickems\Models\BestPick;
use League\Fractal;

class BestPickTransformer extends Fractal\TransformerAbstract
{

    public function transform(BestPick $bestPick)
    {
        return [
            'id' => (int) $bestPick->id,
            'week' => (int) $bestPick->week,
            'pick1' => $bestPick->pick1,
            'pick1_points' => (int) $bestPick->pick1_points,
            'pick1_playmaker' => (bool) $bestPick->pick1_playmaker,
            'pick2' => $bestPick->pick2,
            'pick2_points' => (int) $bestPick->pick2_points,
            'pick2_playmaker' => (bool) $bestPick->pick2_playmaker,
        ];
    }
}
