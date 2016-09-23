<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class NflPlayer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id', 'gsis_id', 'profile_id', 'name', 'position', 'active'
    ];

    public function team()
    {
        return $this->belongsTo(NflTeam::class, 'team_id', 'abbr');
    }

    public function display()
    {
        return $this->name.'-'.$this->team->abbr.'-'.$this->position;
    }

    /**
     * Returns either the fetched nfl player by the gsis id and active
     * flag and if it does not find the player it will create one.  It
     * will also create a new record if the player has changed teams
     *
     * @param  array $data
     * @return Pickems\Models\NflPlayer
     */
    public static function fetchOrCreate($data)
    {
        // try to find the player
        $player = self::where('gsis_id', '=', $data['gsis_id'])->where('active', '=', true)->first();

        if ($player) {
            // if player is different update
            if ($player->team_id != $data['team_id']) {
                // disable the player
                $player->active = false;
                $player->save();

                // create new player
                $player = self::create($data);
            }
        } else {
            // create the player if it doesn't exits
            $player = self::create($data);
        }

        return $player;
    }
}
