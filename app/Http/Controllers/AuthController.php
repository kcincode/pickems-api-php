<?php

namespace Pickems\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;

class AuthController extends Controller
{
    public function token(Request $request)
    {
        // grab credentials from the request
        $credentials = $request->only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (!$access_token = JWTAuth::attempt($credentials)) {
                return $this->renderError('Invalid Credentials', 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return  $this->renderError('Could not create token', 500);
        }

        // all good so return the token
        return response()->json(compact('access_token'));
    }
}
