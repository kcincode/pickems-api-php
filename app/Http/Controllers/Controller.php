<?php

namespace Pickems\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function renderError($message, $code)
    {
        // json api formatted error
        return response()->json([
            'errors' => [
                [
                    'title' => $message,
                    'code' => $code,
                ],
            ],
        ], $code);
    }
}
