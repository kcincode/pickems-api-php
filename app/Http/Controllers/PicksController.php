<?php

namespace Pickems\Http\Controllers;

use Carbon\Carbon;
use Pickems\Models\Team;
use Pickems\Http\Requests;
use Pickems\Models\NflGame;
use Pickems\Models\NflStat;
use Pickems\Models\TeamPick;
use Illuminate\Http\Request;

class PicksController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // authenticate on all routes
        $this->middleware('jwt.auth');
    }

    public function picks(Request $request)
    {
        $params = $this->validateQueryParams($request, ['team' => 'integer', 'week' => 'integer']);

        // make sure there is a team and week specified
        if (!isset($params['team']) or !isset($params['week'])) {
            return $this->renderError('You must specify a team and a week', 400);
        }

        // make sure its a valid week
        if ($params['week'] > 17 or $params['week'] < 1) {
            return $this->renderError('The week paramater must be between 1 and 17', 400);
        }

        // make sure its a valid team
        $team = Team::find($params['team']);
        if (!$team) {
            return $this->renderError('You must specify a valid team', 400);
        }

        // calculate pick data
        list($pick1, $pick2) = $team->calculatePickData($params['week']);

        // calculate picks left and teams picked
        list($picksLeft, $teamsPicked) = $team->validatePicks();

        $response = [
            'week' => $params['week'],
            'schedule' => NflGame::fetchApiSchedule($params['week']),
            'pick1' => $pick1,
            'pick2' => $pick2,
            'picks_left' => $picksLeft,
            'teams_picked' => $teamsPicked,
        ];

        return response()->json($response, 200);
    }

    public function postPicks(Request $request)
    {
        $params = $this->validateQueryParams($request, ['team' => 'integer', 'week' => 'integer', 'pick1' => 'array', 'pick2' => 'array']);

        // make sure there is a team and week specified
        if (!isset($params['team']) or !isset($params['week'])) {
            return $this->renderError('You must specify a team and a week', 400);
        }

        // make sure there is a pick1 and pick2
        if (!isset($params['pick1']) or !isset($params['pick2'])) {
            return $this->renderError('You must specify a pick1 and a pick2', 400);
        }

        // make sure its a valid week
        if ($params['week'] > 17 or $params['week'] < 1) {
            return $this->renderError('The week paramater must be between 1 and 17', 400);
        }

        // make sure its a valid team
        $team = Team::find($params['team']);
        if (!$team) {
            return $this->renderError('You must specify a valid team', 400);
        }

        // make sure the pick1 and pick2 are valid picks
        if (!$this->validatePickData($params['pick1']) || !$this->validatePickData($params['pick2'])) {
            return $this->renderError('The picks are invalid', 400);
        }

        // update the picks
        $this->handlePick($params['pick1'], 1, $params['week'], $team);
        $this->handlePick($params['pick2'], 2, $params['week'], $team);

        // validate all picks
        $team->validatePicks();

        return response()->json(['status' => 'ok'], 200);
    }

    private function validatePickData($pick)
    {
        $validKeys = ['id', 'type', 'playmaker'];

        foreach ($validKeys as $key) {
            if (!isset($pick[$key])) {
                return false;
            }
        }

        return true;
    }

    private function handlePick($pick, $number, $week, $team)
    {
        $dbPick = TeamPick::where('team_id', '=', $team->id)
            ->where('week', '=', $week)
            ->where('number', '=', $number)
            ->first();

        if (!$dbPick) {
            // create basic pick
            $dbPick = new TeamPick();
            $dbPick->team_id = $team->id;
            $dbPick->week = $week;
            $dbPick->number = $number;
            $dbPick->playmaker = $pick['playmaker'];
            $dbPick->valid = true;
            $dbPick->reason = null;
        }

        // update the nfl stat/
        $dbPick->nfl_stat_id = NflStat::updateOrCreate($week, $pick['type'], $pick['id']);

        // TODO: update the picked_at if the pick has changed
        $dbPick->picked_at = Carbon::now();

        // save the pick
        $dbPick->save();
    }
}
