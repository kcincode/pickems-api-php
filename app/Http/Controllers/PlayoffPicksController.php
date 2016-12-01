<?php

namespace Pickems\Http\Controllers;

use Pickems\Models\Team;
use Pickems\Models\NflTeam;
use Pickems\Models\NflGame;
use Illuminate\Http\Request;
use Pickems\Models\NflPlayer;
use Pickems\Models\TeamPlayoffPick;

class PlayoffPicksController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     */
    public function __construct()
    {
        // authenticate on all routes
        // $this->middleware('jwt.auth');
    }

    public function picks($slug)
    {
        $team = Team::where('slug', '=', $slug)->first();

        // make sure there is a valid team specified
        if (!$team) {
            abort(404);
        }

        // get the picks
        $playoffPick = TeamPlayoffPick::fetchOrCreate($team->id);

        // make sure to validate it
        $playoffPick->validate();

        // return the data
        return response()->json($this->renderPlayoffPicks($playoffPick), 200);
    }

    private function renderPlayoffPicks($playoffPick)
    {
        $data = [
            'id' => $playoffPick->id,
            'valid' => (bool) $playoffPick->valid,
            'reason' => $playoffPick->reason,
            'playmakers' => $playoffPick->playmakers,
        ];

        // handle the properties
        foreach($playoffPick->getPicks() as $property) {
            $player = $playoffPick->getPlayer($property)->first();

            $tmp = [];
            if ($player) {
                $tmp = [
                    'id' => $player->gsis_id,
                    'available' => true,
                    'text' => $player->display(),
                ];
            }

            $data[$property] = $tmp;
        }

        return $data;
    }

    public function validPicks()
    {
        $data = [];

        foreach(['qb', 'rb', 'wrte', 'k'] as $position) {
            $data[$position] = [];

            $players = NflPlayer::where('position', '=', strtoupper($position))
                ->where('active', '=', true)
                ->whereIn('team_id', NflTeam::playoffTeams())
                ->orderBy('name')
                ->get();

            foreach($players as $player) {
                $data[$position][] = [
                    'id' => $player->gsis_id,
                    'available' => true,
                    'text' => $player->display(),
                ];
            }
        }

        return response()->json($data, 200);
    }

    public function updatePicks(Request $request, $slug)
    {
        $team = Team::where('slug', '=', $slug)->first();

        // make sure there is a valid team specified
        if (!$team) {
            abort(404);
        }

        $data = $request->input();

        // get the picks
        $playoffPick = TeamPlayoffPick::fetchOrCreate($team->id);
        $playoffPick->update($request->input());
        $playoffPick->validate();
        $playoffPick->save();

        // return the data
        return response()->json($this->renderPlayoffPicks($playoffPick), 200);
    }
}
