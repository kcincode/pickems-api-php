<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class NflGame extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'starts_at',
        'week',
        'type',
        'eid',
        'gsis',
        'home_team_id',
        'away_team_id',
        'winning_team_id',
        'losing_team_id',
    ];

    /**
     * Returns either the fetched nfl game by the eid or if
     * it does not find the game it will create one
     *
     * @param  array $data
     * @return Pickems\Models\NflGame
     */
    public static function fetchOrCreate($data)
    {
        // try to find the game
        $game = self::where('eid', '=', $data['eid'])->first();

        if (!$game) {
            // create the game if it doesn't exits
            $game = self::create($data);
        }

        return $game;
    }
}
