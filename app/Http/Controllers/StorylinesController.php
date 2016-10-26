<?php

namespace Pickems\Http\Controllers;

use Pickems\Models\User;
use Pickems\Models\Storyline;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use Pickems\Http\Requests\StorylineRequest;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\StorylineTransformer;

class StorylinesController extends Controller
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
        $storylines = Storyline::orderBy('week', 'asc')
            ->get();

        // generate resource
        $resource = new Collection($storylines, new StorylineTransformer(), 'storylines');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(StorylineRequest $request)
    {
        // fetch the data
        $data = $request->input('data');

        // create the storyline
        $storyline = new Storyline($data['attributes']);

        // set the user relation
        $storyline->user_id = $data['relationships']['user']['data']['id'];
        $storyline->save();

        // generate resource
        $resource = new Item($storyline, new StorylineTransformer(), 'storylines');

        return $this->jsonResponse($resource, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param Storyline $storyline
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Storyline $storyline)
    {
        // generate resource
        $resource = new Item($storyline, new StorylineTransformer(), 'storylines');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param Storyline                $storyline
     *
     * @return \Illuminate\Http\Response
     */
    public function update(StorylineRequest $request, Storyline $storyline)
    {
        // fetch the data
        $data = $request->input('data');

        // update the storyline
        $storyline->update($data['attributes']);

        // update the user relation
        $storyline->user_id = $data['relationships']['user']['data']['id'];
        $storyline->save();

        // generate resource
        $resource = new Item($storyline, new StorylineTransformer(), 'storylines');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Storyline $storyline
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Storyline $storyline)
    {
        // make sure it can be deleted
        $this->authorize('delete', $storyline);

        // delete the storyline
        $storyline->delete();

        // return empty response
        return response()->json([], 204);
    }
}
