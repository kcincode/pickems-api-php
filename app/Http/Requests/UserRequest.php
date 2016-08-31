<?php

namespace Pickems\Http\Requests;

use Gate;
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

        return Gate::allows(strtolower($this->method()), $this->route('user'));
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
                    'data.id' => 'required|integer',
                    'data.attributes.email' => 'required|email|unique:users,email,'.$this->route('user')->id,
                    'data.attributes.name' => 'required|min:1',
                    'data.attributes.password' => 'min:4',
                ];
        }
    }

    public function messages()
    {
        return [
            'data.type.required' => 'The resource type is required',
            'data.type.in' => 'The resource type must be `users`',
            'data.id.required' => 'The id is required',
            'data.id.integer' => 'The id must be a number',
            'data.attributes.email.required' => 'You must enter an email',
            'data.attributes.email.email' => 'You must enter a valid email',
            'data.attributes.email.unique' => 'The email has already been used',
            'data.attributes.name.required' => 'You must specify a name',
            'data.attributes.password.required' => 'You must enter a password',
            'data.attributes.password.min' => 'The password must be at least 4 characters',
        ];
    }
}
