<?php

namespace Pickems\Http\Controllers;

use Hash;
use Pickems\Models\User;
use Illuminate\Http\Request;
use League\Fractal\Resource\Item;
use Pickems\Http\Requests\UserRequest;
use League\Fractal\Resource\Collection;
use Pickems\Transformers\UserTransformer;

class UsersController extends Controller
{
    /**
     * Instantiate a new new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // authenticate on all routes execpt the create user
        $this->middleware('jwt.auth')->except('store');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::orderBy('name')->get();

        // generate resource
        $resource = new Collection($users, new UserTransformer, 'users');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {
        // fetch the data
        $data = $request->input('data');

        // hash the password
        $data['attributes']['password'] = Hash::make($data['attributes']['password']);

        // create the new user
        $user = new User($data['attributes']);
        $user->save();

        // generate resource
        $resource = new Item($user, new UserTransformer, 'users');

        return $this->jsonResponse($resource, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  User $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        // generate resource
        $resource = new Item($user, new UserTransformer, 'users');

        return $this->jsonResponse($resource, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
