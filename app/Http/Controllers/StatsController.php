<?php

namespace Pickems\Http\Controllers;

use Pickems\Models\Team;
use Pickems\Http\Requests;
use Illuminate\Http\Request;
use Pickems\Models\BestPick;
use Pickems\Models\MostPicked;
use Pickems\Models\WeeklyLeader;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\BestPickTransformer;
use Pickems\Transformers\MostPickedTransformer;
use Pickems\Transformers\WeeklyLeaderTransformer;

class StatsController extends Controller
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

    public function weekly()
    {
        $weeklyLeaders = WeeklyLeader::orderBy('week')->get();

        // generate resource
        $resource = new Collection($weeklyLeaders, new WeeklyLeaderTransformer, 'weekly-leaders');

        return $this->jsonResponse($resource, 200);
    }

    public function best()
    {
        $bestPicks = BestPick::orderBy('week')->get();

        // generate resource
        $resource = new Collection($bestPicks, new BestPickTransformer, 'best-picks');

        return $this->jsonResponse($resource, 200);
    }

    public function most()
    {
        $mostPicked = MostPicked::orderBy('week')->get();

        // generate resource
        $resource = new Collection($mostPicked, new MostPickedTransformer, 'most-picked');

        return $this->jsonResponse($resource, 200);
    }

    public function ranking()
    {
        $data = [
            'Gold' => [],
            'Silver' => [],
            'Bronze' => Team::where('paid', '=', false)
                ->orderBy('points', 'desc')
                ->get(),
        ];

        $paidTeams = Team::where('paid', '=', true)
            ->orderBy('points', 'desc')
            ->get();

        $teamsCount = count($paidTeams);
        $half = ceil(count($paidTeams) / 2);
        foreach($paidTeams as $idx => $team) {
            if ($idx < $half) {
                $data['Gold'][] = $team;
            } else {
                $data['Silver'][] = $team;
            }
        }

        return response()->json($data, 200);
    }

}
