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

    public function display()
    {
        return $this->city.' '.$this->name.'-'.$this->conference;
    }

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

    public static function playoffTeams()
    {
        $teams = [];

        $allPlayoffTeams = NflGame::where('week', '>', 17)->get();
        foreach($allPlayoffTeams as $pTeam) {
            if (!in_array($pTeam->home_team_id, $teams)) {
                $teams[] = $pTeam->home_team_id;
            }

            if (!in_array($pTeam->away_team_id, $teams)) {
                $teams[] = $pTeam->away_team_id;
            }
        }

        return $teams;
    }
}
