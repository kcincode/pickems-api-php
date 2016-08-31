<?php

namespace Pickems\Http\Controllers;

use Illuminate\Http\Request;

use Pickems\Models\Team;
use League\Fractal\Resource\Item;
use Pickems\Http\Requests\TeamRequest;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\TeamTransformer;

class TeamsController extends Controller
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $teams = Team::orderBy('name')->get();

        // generate resource
        $resource = new Collection($teams, new TeamTransformer, 'teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TeamRequest $request)
    {
        // fetch the data
        $data = $request->input('data');
        dd($data);

        // check paid
        if ($data['attributes']['paid'] == true) {
            // make sure the user is an admin to set paid == true
            $authUser = JWTAuth::parseToken()->authenticate();
            if ($authUser->role != 'admin') {
                $data['attributes']['paid'] = false;
            }
        }

        // create the team
        $team = new Team($data['attributes']);

        // set the user relation
        $team->user_id = $data['relationships']['user']['data']['id'];
        $team->save();

        // generate resource
        $resource = new Item($team, new TeamTransformer, 'teams');

        return $this->jsonResponse($resource, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Team $team
     * @return \Illuminate\Http\Response
     */
    public function show(Team $team)
    {
        // generate resource
        $resource = new Item($team, new TeamTransformer, 'teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  Team $team
     * @return \Illuminate\Http\Response
     */
    public function edit(Team $team)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Team $team
     * @return \Illuminate\Http\Response
     */
    public function update(TeamRequest $request, Team $team)
    {
        // fetch the data
        $data = $request->input('data');

        // check paid
        if ($data['attributes']['paid'] == true) {
            // make sure the user is an admin to set paid == true
            $authUser = JWTAuth::parseToken()->authenticate();
            if ($authUser->role != 'admin') {
                $data['attributes']['paid'] = false;
            }
        }

        // update the team
        $team->update($data['attributes']);

        // update the user relation
        $team->user_id = $data['relationships']['user']['data']['id'];
        $team->save();

        // generate resource
        $resource = new Item($team, new TeamTransformer, 'teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Team $team
     * @return \Illuminate\Http\Response
     */
    public function destroy(Team $team)
    {
        // make sure it can be deleted
        $this->authorize('delete', $team);

        // delete the team
        $team->delete();

        // return empty response
        return response()->json([], 204);
    }
}
