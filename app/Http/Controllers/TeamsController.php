<?php

namespace Pickems\Http\Controllers;

use JWTAuth;
use Pickems\Models\User;
use Pickems\Models\Team;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use Pickems\Http\Requests\TeamRequest;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\TeamTransformer;

class TeamsController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     */
    public function __construct()
    {
        // authenticate on all routes
        $this->middleware('jwt.auth')
          ->except('home');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('slug')) {
            $params = $this->validateQueryParams($request, ['slug' => 'string']);
            $teams = Team::where('slug', '=', $params['slug'])
                ->get();
        } else {
            $user = JWTAuth::parseToken()->authenticate();
            $teams = Team::orderBy('name')
                ->where('user_id', '=', $user->id)
                ->get();
        }

        // generate resource
        $resource = new Collection($teams, new TeamTransformer(), 'teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(TeamRequest $request)
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

        // create the team
        $team = new Team($data['attributes']);

        // set the user relation
        $team->user_id = $data['relationships']['user']['data']['id'];
        $team->save();

        // generate resource
        $resource = new Item($team, new TeamTransformer(), 'teams');

        return $this->jsonResponse($resource, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Team $team
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Team $team)
    {
        // generate resource
        $resource = new Item($team, new TeamTransformer(), 'teams');

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
    public function update(TeamRequest $request, Team $team)
    {
        // fetch the data
        $data = $request->input('data');

        // unset paid if user is not an admin
        if (isset($data['attributes']['paid']) && JWTAuth::parseToken()->authenticate()->role != 'admin') {
            unset($data['attributes']['paid']);
        }

        // update the team
        $team->update($data['attributes']);

        // update the user relation
        $team->user_id = $data['relationships']['user']['data']['id'];
        $team->save();

        // generate resource
        $resource = new Item($team, new TeamTransformer(), 'teams');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Team $team
     *
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

    public function home()
    {
        $data = [
            'owners' => User::count() - 1,
            'teams' => [
              'total' => Team::count(),
              'paid' => Team::where('paid', '=', true)->count(),
              'unpaid' => Team::where('paid', '=', false)->count(),
            ],
            'money' => Team::where('paid', '=', true)->count() * 10,
        ];

        return response()->json($data, 200);
    }
}
