<?php

use Pickems\Models\User;
use Pickems\Models\Storyline;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StorylineTest extends TestCase
{
    use DatabaseMigrations;
    protected $attrs = ['week', 'story'];
    protected $relations = ['user'];

    public function testUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/storylines');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidGetRequest()
    {
        // make an invalid request
        $response = $this->callGet('/api/storylines/-1', [], 'user');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidGetRequest()
    {
        $user = factory(User::class)->create();

        // create 2 storylines
        factory(Storyline::class, 2)->create();

        // make a request
        $response = $this->callGet('/api/storylines', [], $user);

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check the number of data points are correct
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');
        $this->assertEquals(2, count($data->data), 'it has the correct number of records');

        foreach ($data->data as $storyline) {
            // make single request
            $singleResponse = $this->callGet('/api/storylines/'.$storyline->id, [], 'user');
            $this->assertEquals(200, $singleResponse->getStatusCode(), 'it has the correct status code');

            // check for correct info
            $singleData = json_decode($singleResponse->content());
            $this->assertNotEmpty($singleData, 'it has returned some data');

            // check the type and id
            $this->assertEquals('storylines', $singleData->data->type, 'it has the correct type');
            $this->assertEquals($storyline->id, $singleData->data->id, 'it has the correct type');

            // check the attributes
            foreach ($this->attrs as $attr) {
                $this->assertEquals($storyline->attributes->$attr, $singleData->data->attributes->$attr, 'attribute matches whats expected');
            }

            // check the relations
            foreach ($this->relations as $relation) {
                $this->assertNotEmpty($singleData->data->relationships->$relation, 'it has the relation');
            }
        }
    }

    public function testUnauthenticatedDeleteRequest()
    {
        // create a team
        $storyline = factory(Storyline::class)->create();

        // make unauthenticated request
        $response = $this->callDelete('/api/storylines/'.$storyline->id);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidDeleteRequest()
    {
        // make invalid request
        $response = $this->callDelete('/api/storylines/-1', 'admin');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedDeleteRequest()
    {
        // create a user and get token for the user
        $storyline = factory(Storyline::class)->create();

        // make invalid request
        $response = $this->callDelete('/api/storylines/'.$storyline->id, 'user');

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidAdminDeleteRequest()
    {
        $storyline = factory(Storyline::class)->create();

        // make valid request
        $response = $this->callDelete('/api/storylines/'.$storyline->id, 'admin');
        $this->assertEquals(204, $response->getStatusCode(), 'it has the correct status code');

        // check to make sure the team is no longer in the database
        $this->assertEmpty(Storyline::find($storyline->id), 'the team does not exist in the database');
    }

    public function testUnauthenticatedPostRequest()
    {
        // make unauthenticated request
        $response = $this->callPost('/api/storylines', json_encode([]));

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidPostRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);

        $postData = [
            'data' => [
                'type' => 'storylines',
                'attributes' => [
                    'week' => 3,
                    'story' => 'Test Story',
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make a request for a token
        $response = $this->callPost('/api/storylines', json_encode($postData), $user);

        // check status code
        $this->assertEquals(201, $response->status(), 'it returns a 201 status');

        // get the data
        $data = json_decode($response->content(), true);

        // check to make sure that user id and object exists
        $this->assertTrue(isset($data['data']['id']), 'the response has an id');
        $user = User::find($data['data']['id']);
        $this->assertNotNull($user, 'the user object exists in the database');
        $this->assertFalse('testing' == $user->password, 'the users password is not in plain text');
    }

    public function testUnauthenticatedPatchRequest()
    {
        // create a team
        $storyline = factory(Storyline::class)->create();

        // make unauthenticated request
        $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode([]));

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/storylines/-1', json_encode([]), $user);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $storyline = factory(Storyline::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode([]), $user);

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testAuthorizedAdminPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $storyline = factory(Storyline::class)->create();

        $patchData = [
            'data' => [
                'type' => 'storylines',
                'id' => $storyline->id,
                'attributes' => [
                    'week' => 5,
                    'story' => 'This is an updated storyline',
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $storyline->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make invalid request
        $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode($patchData), 'admin');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check attributes
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');

        // check the attributes
        foreach ($patchData['data']['attributes'] as $attr => $value) {
            $this->assertEquals($value, $data->data->attributes->$attr, 'attribute matches whats expected');
        }
    }

    public function testInvalidIdPatchRequest()
    {
        $storyline = factory(Storyline::class)->create();

        $patchData = [
            'data' => [
                'type' => 'storylines',
                'attributes' => [
                    'week' => 5,
                    'story' => 'This is an updated storyline',
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $storyline->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make invalid request
        $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode($patchData), 'admin');

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('The id is required', $errors[0]->title, 'it has the correct error message');
    }

    public function testInvalidTypePatchRequest()
    {
        $storyline = factory(Storyline::class)->create();

        $origPatchData = [
            'data' => [
                'type' => 'storylines',
                'id' => $storyline->id,
                'attributes' => [
                    'week' => 5,
                    'story' => 'This is an updated storyline',
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $storyline->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // the types and expected messages
        $types = [
            null => 'The resource type is required',
            'bleh' => 'The resource type must be `storylines`',
        ];

        foreach ($types as $type => $typeMessage) {
            $patchData = $origPatchData;
            $patchData['data']['type'] = $type;

            // make an invalid request for a token
            $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode($patchData), 'admin');

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($typeMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testInvalidStoryPatchRequest()
    {
        // create already taken team
        $storyline = factory(Storyline::class)->create();
        $otherTeam = factory(Storyline::class)->create();

        $origPatchData = [
            'data' => [
                'type' => 'storylines',
                'id' => $storyline->id,
                'attributes' => [
                    'week' => 5,
                    'story' => 'This is an updated storyline',
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $storyline->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // the emails and expected messages
        $storys = [
            null => 'You must enter a story',
        ];

        foreach ($storys as $story => $storyMessage) {
            $patchData = $origPatchData;
            $patchData['data']['attributes']['story'] = $story;

            // make an invalid request for a token
            $response = $this->callPatch('/api/storylines/'.$storyline->id, json_encode($patchData), 'admin');

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($storyMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }
}
