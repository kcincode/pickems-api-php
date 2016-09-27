<?php

namespace Pickems\Models;

use Auth;
use Pickems\Models\TeamPick;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'slug', 'paid', 'points', 'playoffs', 'wl',
    ];

    protected $casts = [
        'paid' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function teamPicks()
    {
        return $this->hasMany(TeamPick::class)
            ->orderBy('week', 'asc')
            ->orderBy('number', 'asc');
    }

    public function calculatePickData($week)
    {
        // default pick data
        $pick1 = $pick2 = [
            'selected' => null,
            'disabled' => $week < NflGame::currentWeekRegularSeason(),
            'id' => null,
            'type' => null,
            'valid' => true,
            'reason' => null,
            'playmaker' => false,
        ];

        if ($this->user_id == Auth::id()) {
            $teamPicks = TeamPick::where('team_id', '=', $this->id)
                ->where('week', '=', $week)
                ->orderBy('number', 'asc')
                ->get();

            foreach ($teamPicks as $teamPick) {
                $key = 'pick'. $teamPick->number;

                $$key['type'] = ($teamPick->nfl_stat->player_id) ? 'player' : 'team';
                $$key['id'] = ($teamPick->nfl_stat->player_id) ? (int) $teamPick->nfl_stat->player_id : (int) $teamPick->nfl_stat->team_id;
                $$key['reason'] = $teamPick->reason;
                $$key['valid'] = (bool) $teamPick->valid;
                $$key['playmaker'] = (bool) $teamPick->playmaker;
            }
        }


        return [
            $pick1,
            $pick2,
        ];
    }

    public function validatePicks()
    {
        $teamsPicked = [];

        $picksLeft = [
            'QB' => 8,
            'RB' => 8,
            'WRTE' => 8,
            'K' => 8,
            'playmakers' => 2,
            'afc' => 1,
            'nfc' => 1,
        ];

        foreach ($this->teamPicks as $teamPick) {
            if (!$teamPick->nfl_stat) {
                continue;
            }

            if ($teamPick->nfl_stat->player_id) {
                $pick = $teamPick->nfl_stat->player;

                // check playmaker
                if ($teamPick->playmaker && $picksLeft['playmakers'] <= 0) {
                    // error in pick
                    $teamPick->valid = false;
                    $teamPick->reason = 'You have already used your playmakers';
                } elseif ($picksLeft[$pick->position] <= 0) {
                    // error in pick
                    $teamPick->valid = false;
                    $teamPick->reason = 'You have already picked all players from the position '.$pick->position;
                } elseif (in_array($pick->team->abbr, $teamsPicked)) {
                    $teamPick->valid = false;
                    $teamPick->reason = 'You have already picked a player from the team '.$pick->team->abbr;
                } else {
                    // player pick
                    $picksLeft[$pick->position]--;
                    $teamsPicked[] = $pick->team->abbr;

                    if ($teamPick->playmaker) {
                        $picksLeft['playmakers']--;
                    }
                }
            } else {
                $pick = $teamPick->nfl_stat->team;

                if ($picksLeft[strtolower($pick->conference)] <= 0) {
                    // error in pick
                    $teamPick->valid = false;
                    $teamPick->reason = 'You have already picked a team from the conference '.$pick->conference;
                } else {
                    // team pick
                    $picksLeft[strtolower($pick->conference)]--;
                }
            }

            $pick->save();
        }

        // calculate all teams picked status
        $allTeamsPicked = ['afc' => [], 'nfc' => []];
        foreach (NflTeam::orderBy('city')->orderBy('name')->get() as $nflTeam) {
            $allTeamsPicked[strtolower($nflTeam->conference)][] = [
                'abbr' => $nflTeam->abbr,
                'name' => $nflTeam->city.' '.$nflTeam->name,
                'available' => !in_array($nflTeam->abbr, $teamsPicked),
            ];
        }

        return [$picksLeft, $allTeamsPicked];
    }
}
