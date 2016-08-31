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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Team $team
     * @return \Illuminate\Http\Response
     */
    public function destroy(Team $team)
    {
        //
    }
}
