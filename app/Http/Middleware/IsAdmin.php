<?php

namespace Pickems\Http\Middleware;

use Closure;
use Guard;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // check if a user is logged in and is in the admin role
        if (Auth::check() && Auth::user()->role !== 'admin') {
            return response()->json(['errors' => [['title' => 'Unauthourized', 'detail' => 'You must be an admin to access this resource']]], 401);
        }

        return $next($request);
    }
}
