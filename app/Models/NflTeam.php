<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class NflTeam extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'abbr', 'conference', 'city', 'name', 'wl'
    ];

    /**
     * Returns either the fetched nfl team by the abbr or if
     * it does not find the team it will create one
     *
     * @param  array $data
     * @return Pickems\Models\NflTeam
     */
    public static function fetchOrCreate($data)
    {
        // try to find the team
        $team = self::where('abbr', '=', $data['abbr'])->first();

        if (!$team) {
            // create the team if it doesn't exits
            $team = self::create($data);
        }

        return $team;
    }
}
