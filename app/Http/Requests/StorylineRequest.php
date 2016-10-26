<?php

namespace Pickems\Http\Requests;

use Gate;
use Pickems\Models\Storyline;

class StorylineRequest extends JsonApiRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $storyline = ($this->route('storyline')) ? $this->route('storyline') : Storyline::class;

        return Gate::allows(strtolower($this->method()), $storyline);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
          'data.type' => 'required|in:storylines',
          'data.id' => 'required|integer',
          'data.attributes.week' => 'required|integer',
          'data.attributes.story' => 'required',
          'data.relationships.user.data.id' => 'required|integer',
          'data.relationships.user.data.type' => 'required|in:users',
        ];

        // don't need an id in a post request
        if ($this->method() == 'POST') {
            unset($rules['data.id']);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'data.type.required' => 'The resource type is required',
            'data.type.in' => 'The resource type must be `storylines`',
            'data.id.required' => 'The id is required',
            'data.id.integer' => 'The id must be a number',
            'data.attributes.week.required' => 'You must specify a week',
            'data.attributes.week.integer' => 'The week must be a number',
            'data.attributes.story.required' => 'You must enter a story',
            'data.relationships.user.data.type.required' => 'The user resource type is required',
            'data.relationships.user.data.type.in' => 'The user resource type must be `users`',
            'data.relationships.user.data.id.required' => 'The user id is required',
            'data.relationships.user.data.id.integer' => 'The user id must be a number',
        ];
    }
}
