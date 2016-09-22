<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyLeader extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'week', 'team_id', 'team', 'points'
    ];
}
