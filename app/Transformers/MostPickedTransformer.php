<?php
namespace Pickems\Transformers;

use Pickems\Models\MostPicked;
use League\Fractal;

class MostPickedTransformer extends Fractal\TransformerAbstract
{

    public function transform(MostPicked $mostPicked)
    {
        return [
            'id' => (int) $mostPicked->id,
            'week' => (int) $mostPicked->week,
            'name' => $mostPicked->name,
            'number_picked' => (int) $mostPicked->number_picked,
        ];
    }
}
