<?php

namespace Pickems\Http\Middleware;

use App;
use Closure;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Access-Control-Allow-Origin', $this->getFrontendUrl());
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS, POST, PATCH, DELETE');

        return $response;
    }

    private function getFrontendUrl()
    {
        switch (App::environment()) {
        case 'production':
          return 'https://pickems.surge.sh';
        default:
          return 'http://localhost:4200';
      }
    }
}
