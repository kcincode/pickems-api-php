<?php

namespace Pickems\Http\Controllers;

use Carbon\Carbon;
use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflStat;
use Pickems\Models\TeamPick;
use Pickems\Models\NflTeam;
use Pickems\Models\NflPlayer;
use Illuminate\Http\Request;

class PicksController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     */
    public function __construct()
    {
        // authenticate on all routes
        $this->middleware('jwt.auth');
    }

    public function picks(Request $request)
    {
        $params = $this->validateQueryParams($request, ['team' => 'string', 'week' => 'integer']);

        // make sure there is a team and week specified
        if (!isset($params['team']) or !isset($params['week'])) {
            return $this->renderError('You must specify a team and a week', 400);
        }

        // make sure its a valid week
        if ($params['week'] > 17 or $params['week'] < 1) {
            return $this->renderError('The week paramater must be between 1 and 17', 400);
        }

        // make sure its a valid team
        $team = Team::where('slug', '=', $params['team'])->first();
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
        $params = $this->validateQueryParams($request, ['team' => 'string', 'week' => 'integer', 'pick1' => 'array', 'pick2' => 'array']);

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
        $team = Team::where('slug', '=', $params['team'])->first();
        if (!$team) {
            return $this->renderError('You must specify a valid team', 400);
        }

        // check if the week is not valid
        if ($params['week'] < NflGame::currentWeekRegularSeason()) {
            return $this->renderError('You may not submit picks for past weeks after they are finished', 400);
        }

        // handle the picks
        $this->handlePick($params['pick1'], 1, $params['week'], $team);
        $this->handlePick($params['pick2'], 2, $params['week'], $team);

        // validate all picks
        $team->validatePicks();

        return response()->json(['status' => 'ok'], 200);
    }

    private function validatePickData($pick)
    {
        $validKeys = ['id', 'type', 'playmaker'];

        // make sure each key is present
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

        // don't make a pick if the values are empty
        if (empty($pick['type'])) {
            if ($dbPick) {
                $dbPick->delete();
            }

            return;
        }

        if (!$dbPick) {
            // create basic pick
            $dbPick = new TeamPick();
            $dbPick->team_id = $team->id;
            $dbPick->week = $week;
            $dbPick->number = $number;
            $dbPick->valid = true;
            $dbPick->reason = null;
        }

        // update the nfl stat/
        $stat = NflStat::updateOrCreate($week, $pick['type'], $pick['id']);
        $dbPick->nfl_stat_id = $stat->id;

        // update playmaker
        $dbPick->playmaker = $pick['playmaker'];

        // update the picked_at if the pick has changed
        if ($dbPick->isDirty()) {
            $dbPick->picked_at = Carbon::now();
        }

        // save the pick
        $dbPick->save();
    }

    public function allPicks($slug)
    {
        $team = Team::where('slug', '=', $slug)->first();
        if (!$team) {
            return $this->renderError('Invalid team specified', 400);
        }

        $currentWeek = NflGame::currentWeekRegularSeason();

        $teamPicks = [];
        $overallTotal = 0;
        foreach ($team->teamPicks as $teamPick) {
            if ($teamPick->week >= $currentWeek) {
                continue;
            }

            // create the entry if it doesn't exist
            if (!isset($teamPicks[$teamPick->week])) {
                $teamPicks[$teamPick->week] = [
                    'week' => $teamPick->week,
                    'pick1' => null,
                    'pick1_points' => 0,
                    'pick1_playmaker' => false,
                    'pick1_valid' => true,
                    'pick1_reason' => null,
                    'pick2' => null,
                    'pick2_points' => 0,
                    'pick2_playmaker' => false,
                    'pick2_valid' => true,
                    'pick2_reason' => null,
                    'total' => 0,
                ];
            }

            // update the data
            $teamPicks[$teamPick->week]['pick'.$teamPick->number] = ($teamPick->nfl_stat->player_id) ? $teamPick->nfl_stat->player->display() : $teamPick->nfl_stat->team->display();
            $teamPicks[$teamPick->week]['pick'.$teamPick->number.'_points'] = $teamPick->points();
            $teamPicks[$teamPick->week]['pick'.$teamPick->number.'_playmaker'] = $teamPick->playmaker;
            $teamPicks[$teamPick->week]['pick'.$teamPick->number.'_valid'] = $teamPick->valid;
            $teamPicks[$teamPick->week]['pick'.$teamPick->number.'_reason'] = $teamPick->reason;

            $total = $teamPicks[$teamPick->week]['pick1_points'] + $teamPicks[$teamPick->week]['pick2_points'];
            $teamPicks[$teamPick->week]['total'] = $total;

            $overallTotal += $teamPicks[$teamPick->week]['pick'.$teamPick->number.'_points'];
        }

        // total up the points
        $teamPicks[18] = [
            'week' => 18,
            'pick1' => null,
            'pick1_points' => 0,
            'pick1_playmaker' => false,
            'pick1_valid' => true,
            'pick1_reason' => null,
            'pick2' => null,
            'pick2_points' => 0,
            'pick2_playmaker' => false,
            'pick2_valid' => true,
            'pick2_reason' => null,
            'total' => $overallTotal,
        ];

        return response()->json(array_values($teamPicks), 200);
    }

    public function filter(Request $request)
    {
        $term = $request->input('term');
        if (empty($term)) {
            return response()->json([], 200);
        }

        $results = [];
        $players = NflPlayer::where('name', 'ILIKE', '%'.$term.'%')
            ->where('active', '=', 1)
            ->get();

        foreach ($players as $player) {
            $results[] = [
              'id' => $player->gsis_id,
              'type' => 'player',
              'available' => true,
              'text' => $player->display(),
            ];
        }

        $teams = NflTeam::where('abbr', 'ILIKE', '%'.$term.'%')
            ->orWhere('city', 'ILIKE', '%'.$term.'%')
            ->orWhere('conference', 'ILIKE', '%'.$term.'%')
            ->orWhere('name', 'ILIKE', '%'.$term.'%')
            ->get();

        foreach ($teams as $team) {
            $results[] = [
              'id' => $team->abbr,
              'type' => 'team',
              'available' => true,
              'text' => $team->display(),
            ];
        }

        return response($results, 200);
    }

    public function playoffFilter(Request $request)
    {
        $term = $request->input('term');
        if (empty($term)) {
            return $this->renderError('You must specify at least a term to search on', 400);
        }

        $position = $request->input('position');
        if (empty($position)) {
            return $this->renderError('You must specify a position to search on', 400);
        }

        $results = [];
        $players = NflPlayer::where('name', 'ILIKE', '%'.$term.'%')
            ->where('position', '=', $position)
            ->where('active', '=', true)
            ->get();

        foreach ($players as $player) {
            $results[] = [
              'id' => $player->gsis_id,
              'available' => true,
              'text' => $player->display(),
            ];
        }

        return response($results, 200);
    }
}
