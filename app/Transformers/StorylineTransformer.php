<?php

namespace Pickems\Transformers;

use Pickems\Models\Storyline;
use League\Fractal;

class StorylineTransformer extends Fractal\TransformerAbstract
{
    protected $defaultIncludes = ['user'];

    public function transform(Storyline $storyline)
    {
        return [
            'id' => (int) $storyline->id,
            'week' => (int) $storyline->week,
            'story' => $storyline->story,
        ];
    }

    public function includeUser(Storyline $storyline)
    {
        if ($storyline->user) {
            return $this->item($storyline->user, new UserTransformer(), 'user');
        }

        return null;
    }
}
