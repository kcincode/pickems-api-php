<?php

namespace Pickems\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class JsonApiRequest extends FormRequest
{
    /**
     * Return the array of errors formatted to JSON API format
     *
     * @param  Validator $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        // loop through the errors and add them to the array
        $errors = [];
        foreach ($validator->errors()->messages() as $key => $error) {
            // format the errors in JSON Api format
            $errors[] = [
                'status' => 422,
                'title' => $error[0],
                'source' => [
                    'pointer' => str_replace('.', '/', $key),
                ],
            ];
        }

        return $errors;
    }

    /**
     * Handle the errors in json format
     *
     * @param  array  $errors
     * @return JsonResponse
     */
    public function response(array $errors)
    {
        // return the JSON API error
        return response()->json(['errors' => $errors], 422);
    }
}
