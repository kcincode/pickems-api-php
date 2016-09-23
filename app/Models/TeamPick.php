<?php

namespace Pickems\Models;

use Pickems\Models\Team;
use Pickems\Models\NflStat;
use Illuminate\Database\Eloquent\Model;

class TeamPick extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id', 'week', 'number', 'nfl_stat_id', 'playmaker', 'valid', 'reason', 'picked_at'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function nfl_stat()
    {
        return $this->belongsTo(NflStat::class, 'nfl_stat_id');
    }

    public function points()
    {
        if ($this->nfl_stat_id) {
            $multiplier = ($this->playmaker) ? 2 : 1;

            return $this->nfl_stat->points() * $multiplier;
        }

        return 0;
    }
}
