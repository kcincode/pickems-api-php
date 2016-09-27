<?php

namespace Pickems\Http\Requests;

use Gate;
use Auth;
use Pickems\Models\Team;
use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends JsonApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->method == 'POST') {
            return true;
        }

        return Gate::allows(strtolower($this->method()), $this->route('team'));
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
                    'data.type' => 'required|in:teams',
                    'data.attributes.name' => 'required|unique:teams,name',
                    'data.attributes.slug' => 'required|unique:teams,slug',
                    'data.attributes.paid' => 'required',
                    'data.relationships.user.data.id' => 'required|integer',
                    'data.relationships.user.data.type' => 'required|in:users',
                ];
            case 'PUT':
            case 'PATCH':
                return [
                    'data.type' => 'required|in:teams',
                    'data.id' => 'required|integer',
                    'data.attributes.name' => 'required|unique:teams,name,'.$this->route('team')->id,
                    'data.attributes.slug' => 'required|unique:teams,slug,'.$this->route('team')->id,
                    'data.attributes.paid' => 'required',
                    'data.relationships.user.data.id' => 'required|integer',
                    'data.relationships.user.data.type' => 'required|in:users',
                ];
        }
    }

    public function messages()
    {
        return [
            'data.attributes.name.required' => 'You must specify a name',
            'data.attributes.name.unique' => 'The name has already been used',
            'data.attributes.slug.unique' => 'The name has already been used',
            'data.attributes.paid.required' => 'You must enter a password',
            'data.type.required' => 'The resource type is required',
            'data.type.in' => 'The resource type must be `teams`',
            'data.id.required' => 'The id is required',
            'data.id.integer' => 'The id must be a number',
            'data.relationships.user.data.type.required' => 'The user resource type is required',
            'data.relationships.user.data.type.in' => 'The user resource type must be `users`',
            'data.relationships.user.data.id.required' => 'The user id is required',
            'data.relationships.user.data.id.integer' => 'The user id must be a number',
        ];
    }
}
