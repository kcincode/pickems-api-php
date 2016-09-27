<?php

namespace Pickems\Http\Controllers;

use League\Fractal\Manager;
use Illuminate\Foundation\Bus\DispatchesJobs;
use League\Fractal\Serializer\JsonApiSerializer;
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

    public function jsonResponse($resource, $code)
    {
        // setup fractal to render JSON API
        $manager = new Manager();
        $manager->setSerializer(new JsonApiSerializer());

        // setup to parse incldues
        if (isset($_GET['include'])) {
            $manager->parseIncludes($_GET['include']);
        }

        // return the resource and code
        return response()->json($manager->createData($resource)->toArray(), $code);
    }

    public function validateQueryParams($request, $expectedParams)
    {
        $filteredParams = [];
        $params = $request->all();

        foreach ($expectedParams as $param => $paramType) {
            $method = 'validateQueryParam'.ucfirst($paramType);
            if (isset($params[$param]) and call_user_func_array([$this, $method], [$params[$param]])) {
                $filteredParams[$param] = $this->castData($params[$param], $paramType);
            }
        }

        return $filteredParams;
    }

    private function castData($data, $type)
    {
        switch ($type) {
            case 'integer':
                return (int) $data;
            default:
                return $data;
        }
    }

    private function validateQueryParamInteger($data)
    {
        return is_numeric($data);
    }

    private function validateQueryParamArray($data)
    {
        return is_array($data);
    }

    private function validateQueryParamString($data)
    {
        return strlen($data) > 0;
    }
}
