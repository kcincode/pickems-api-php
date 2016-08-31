<?php

namespace Pickems\Http\Requests;

use JWTAuth;

class UserRequest extends JsonApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // allow anyone to create a user
        if ($this->method() == 'POST') {
            return true;
        }

        // dd(JWTAuth::parseToken()->authenticate());

        return Gate::allows($this->method(), JWTAuth::parseToken()->authenticate());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        switch ($this->method()) {
            case 'POST':
                return [
                    'data.type' => 'required|in:users',
                    'data.attributes.email' => 'required|email|unique:users,email',
                    'data.attributes.name' => 'required',
                    'data.attributes.password' => 'required|min:4',
                ];
            case 'PUT':
            case 'PATCH':
                return [
                    'data.type' => 'required|in:users',
                    'data.id' => 'required|number',
                    'data.attributes.email' => 'email|unique:users,email,'.$this->route('user'),
                    'data.attributes.name' => 'min:1',
                    'data.attributes.password' => 'min:4',
                ];
        }
    }

    public function messages()
    {
        return [
            'data.type.required' => 'The resource type is required',
            'data.type.in' => 'The resource type must be `users`',
            'data.attributes.email.required' => 'You must enter an email',
            'data.attributes.email.email' => 'You must enter a valid email',
            'data.attributes.email.unique' => 'The email has already been used',
            'data.attributes.name.required' => 'You must specify a name',
            'data.attributes.password.required' => 'You must enter a password',
            'data.attributes.password.min' => 'The password must be at least 4 characters',
        ];
    }
}
