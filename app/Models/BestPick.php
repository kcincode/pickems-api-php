<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class BestPick extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'week',
        'pick1',
        'pick1_points',
        'pick1_playmaker',
        'pick2',
        'pick2_points',
        'pick2_playmaker',
        'total',
    ];
}
