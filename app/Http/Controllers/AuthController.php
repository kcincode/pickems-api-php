<?php

namespace Pickems\Http\Controllers;

use JWTAuth;
use Pickems\Models\User;
use Pickems\Models\NflGame;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        // grab credentials from the request
        $credentials = [
            'email' => $request->input('username'),
            'password' => $request->input('password'),
        ];

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$access_token = JWTAuth::attempt($credentials)) {
                return $this->renderError('Invalid Credentials', 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return  $this->renderError('Could not create token', 500);
        }

        // get the user id
        $user = User::where('email', '=', $credentials['email'])
            ->first();
        $user_id = $user->id;
        $role = $user->role;

        $current_week = 18;//NflGame::currentWeekNumber();
        $hasPlayoffsStarted = false;//NflGame::hasPlayoffsStarted();

        // all good so return the token
        return response()->json(compact('access_token', 'user_id', 'current_week', 'hasPlayoffsStarted', 'role'));
    }
}
