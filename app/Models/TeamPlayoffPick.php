<?php

namespace Pickems\Models;

use Log;
use Illuminate\Database\Eloquent\Model;

class TeamPlayoffPick extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id', 'qb1', 'qb2', 'rb1', 'rb2', 'rb3', 'wrte1', 'wrte2', 'wrte3', 'wrte4', 'wrte5', 'k1', 'k2', 'playmakers', 'valid', 'reason'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function getPlayer($property)
    {
        return $this->belongsTo(NflPlayer::class, $property, 'gsis_id');
    }

    private function allIds()
    {
        return [
            $this->qb1,
            $this->qb2,
            $this->rb1,
            $this->rb2,
            $this->rb3,
            $this->wrte1,
            $this->wrte2,
            $this->wrte3,
            $this->wrte4,
            $this->wrte5,
            $this->k1,
            $this->k2,
        ];
    }

    public function getPicks()
    {
        return array_filter($this->fillable, function($key) {
            return !in_array($key, ['team_id', 'playmakers', 'valid', 'reason']);
        });

    }

    public static function fetchOrCreate($teamId, $picks = [])
    {
        // try to find or create the picks
        $result = static::where('team_id', '=', $teamId)->first();
        if (!$result) {
            $result = new self();
            $result->team_id = $teamId;
        }

        // for each of the valid picks assign them
        foreach ($picks as $key => $pick) {
            $result->$key = $pick;
        }

        $result->save();

        // return the data
        return $result;
    }

    public function points()
    {
        // setup the playmaker ids
        $playmakerIds = explode(',', $this->playmakers);
        // initialize the points with the starting points
        $points = $this->team->playoffs;

        // loop through all of the player ids in the playoffs
        foreach(NflStat::whereIn('player_id', $this->allIds())->where('week', '>', 17)->get() as $nflStat) {
            // double the multiplier if playmaker
            $multiplier = (in_array($nflStat->player_id, $playmakerIds)) ? 2 : 1;

            // increment the points
            $points += $nflStat->points() * $multiplier;
        }

        // return the sum
        return $points;
    }

    public function pointDetails()
    {
        // setup the playmaker ids
        $playmakerIds = explode(',', $this->playmakers);
        $points = [];

        // loop through all of the player ids in the playoffs
        foreach(NflStat::whereIn('player_id', $this->allIds())->where('week', '>', 17)->get() as $nflStat) {
            // double the multiplier if playmaker
            $multiplier = (in_array($nflStat->player_id, $playmakerIds)) ? 2 : 1;

            // initialize the data
            if (!isset($points[$nflStat->player_id])) {
                $points[$nflStat->player_id] = [
                    18 => 0,
                    19 => 0,
                    20 => 0,
                    22 => 0,
                ];
            }

            $points[$nflStat->player_id][$nflStat->week] = $nflStat->points() * $multiplier;
        }

        // return the sum
        return $points;
    }

    public function validate()
    {
        $usedTeams = [];

        // reset the status
        $this->valid = true;
        $this->reason = null;
        $this->save();

        try {
            foreach($this->getPicks() as $property) {
                $player = $this->getPlayer($property)->first();

                if ($player) {
                    // check if team has already been used
                    if (in_array($player->team->abbr, $usedTeams)) {
                        throw new \Exception('Duplicate team '.$player->team->abbr.' used');
                    }

                    // add used team to array
                    $usedTeams[] = $player->team->abbr;

                    // check if valid position
                    $key = preg_replace('/[0-9]/i', '', $property);
                    if (strtolower($player->position) != $key) {
                        throw new \Exception('Invalid position selection for '.$property. ': '.$player->position);
                    }
                }
            }

            // check for playmakers
            $playmakers = explode(',', $this->playmakers);
            if (count($playmakers) > 2) {
                throw new \Exception('Too many playmakers specified');
            }
        } catch(\Exception $e) {
            $this->valid = false;
            $this->reason = $e->getMessage();
            $this->save();
        }
    }
}
