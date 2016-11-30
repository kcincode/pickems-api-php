<?php

namespace Pickems\Http\Controllers;

use Pickems\Models\Team;
use Illuminate\Http\Request;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\TeamTransformer;
use Pickems\Http\Requests\AdminTeamRequest;

class AdminTeamsController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     */
    public function __construct()
    {
        // authenticate on all routes
        $this->middleware('jwt.auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $teams = Team::orderBy('name');
        if ($request->has('filter')) {
            $params = $this->validateQueryParams($request, ['filter' => 'string']);
            $teams->where('name', 'LIKE', '%'.strtolower($params['filter']).'%');
        }

        // generate resource
        $resource = new Collection($teams->get(), new TeamTransformer(), 'admin-teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Team                     $team
     *
     * @return \Illuminate\Http\Response
     */
    public function update(AdminTeamRequest $request, Team $team)
    {
        // fetch the data
        $data = $request->input('data');

        // update the team
        $team->update($data['attributes']);

        // update the user relation
        $team->user_id = $data['relationships']['user']['data']['id'];
        $team->save();

        // generate resource
        $resource = new Item($team, new TeamTransformer(), 'teams');

        return $this->jsonResponse($resource, 200);
    }
}
