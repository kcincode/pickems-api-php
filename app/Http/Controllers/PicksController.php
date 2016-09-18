<?php

namespace Pickems\Http\Controllers;

use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Http\Requests;
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
        list($picksLeft, $teamsPicked) = $team->calculatePicksLeft();

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
}
