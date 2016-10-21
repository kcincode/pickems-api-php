<?php

use Pickems\Models\User;
use Pickems\Models\Team;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class TeamsTest extends TestCase
{
    use DatabaseMigrations;
    protected $attrs = ['name', 'slug', 'paid', 'points', 'playoffs', 'wl'];
    protected $relations = ['user'];

    public function testUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/teams');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidGetRequest()
    {
        // make an invalid request
        $response = $this->callGet('/api/teams/-1', [], 'user');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidGetRequest()
    {
        $user = factory(User::class)->create();

        // create 2 teams
        factory(Team::class, 2)->create(['user_id' => $user->id]);

        // create 5 other teams (should not display)
        factory(Team::class, 5)->create();

        // make a request
        $response = $this->callGet('/api/teams', [], $user);

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check the number of data points are correct
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');
        $this->assertEquals(2, count($data->data), 'it has the correct number of records');

        foreach ($data->data as $team) {
            // make single request
            $singleResponse = $this->callGet('/api/teams/'.$team->id, [], 'user');
            $this->assertEquals(200, $singleResponse->getStatusCode(), 'it has the correct status code');

            // check for correct info
            $singleData = json_decode($singleResponse->content());
            $this->assertNotEmpty($singleData, 'it has returned some data');

            // check the type and id
            $this->assertEquals('teams', $singleData->data->type, 'it has the correct type');
            $this->assertEquals($team->id, $singleData->data->id, 'it has the correct type');

            // check the attributes
            foreach ($this->attrs as $attr) {
                $this->assertEquals($team->attributes->$attr, $singleData->data->attributes->$attr, 'attribute matches whats expected');
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
        $team = factory(Team::class)->create();

        // make unauthenticated request
        $response = $this->callDelete('/api/teams/'.$team->id);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidDeleteRequest()
    {
        // make invalid request
        $response = $this->callDelete('/api/teams/-1', 'admin');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedDeleteRequest()
    {
        // create a user and get token for the user
        $team = factory(Team::class)->create();

        // make invalid request
        $response = $this->callDelete('/api/teams/'.$team->id, 'user');

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidAdminDeleteRequest()
    {
        $team = factory(Team::class)->create();

        // make valid request
        $response = $this->callDelete('/api/teams/'.$team->id, 'admin');
        $this->assertEquals(204, $response->getStatusCode(), 'it has the correct status code');

        // check to make sure the team is no longer in the database
        $this->assertEmpty(Team::find($team->id), 'the team does not exist in the database');
    }

    public function testValidUserDeleteRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create(['user_id' => $user->id]);

        // make valid request
        $response = $this->callDelete('/api/teams/'.$team->id, $user);
        $this->assertEquals(204, $response->getStatusCode(), 'it has the correct status code');

        // check to make sure the team is no longer in the database
        $this->assertEmpty(Team::find($team->id), 'the team does not exist in the database');
    }

    public function testUnauthenticatedPostRequest()
    {
        // make unauthenticated request
        $response = $this->callPost('/api/teams', json_encode([]));

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidPostRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();

        $postData = [
            'data' => [
                'type' => 'teams',
                'attributes' => [
                    'name' => 'Test Team',
                    'slug' => 'test-team',
                    'paid' => true,
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
        $response = $this->callPost('/api/teams', json_encode($postData), $user);

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
        $team = factory(Team::class)->create();

        // make unauthenticated request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode([]));

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/teams/-1', json_encode([]), $user);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode([]), $user);

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testAuthorizedAdminPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $team = factory(Team::class)->create(['paid' => false]);

        $patchData = [
            'data' => [
                'type' => 'teams',
                'id' => $team->id,
                'attributes' => [
                    'name' => 'mod '.$team->name,
                    'slug' => str_slug('mod'.$team->name),
                    'paid' => true,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make invalid request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), 'admin');

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

    public function testAuthorizedNonAdminPatchRequest()
    {
        $team = factory(Team::class)->create(['paid' => false]);

        $patchData = [
            'data' => [
                'type' => 'teams',
                'id' => $team->id,
                'attributes' => [
                    'name' => 'mod '.$team->name,
                    'slug' => str_slug('mod'.$team->name),
                    'paid' => false,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make invalid request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), $team->user);

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
        $team = factory(Team::class)->create();

        $patchData = [
            'data' => [
                'type' => 'teams',
                'attributes' => [
                    'name' => 'mod '.$team->name,
                    'slug' => str_slug('mod'.$team->name),
                    'paid' => false,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // make invalid request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), $team->user);

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('The id is required', $errors[0]->title, 'it has the correct error message');
    }

    public function testInvalidTypePatchRequest()
    {
        $team = factory(Team::class)->create();

        $origPatchData = [
            'data' => [
                'type' => 'teams',
                'id' => $team->id,
                'attributes' => [
                    'name' => 'mod '.$team->name,
                    'slug' => str_slug('mod'.$team->name),
                    'paid' => false,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // the types and expected messages
        $types = [
            null => 'The resource type is required',
            'bleh' => 'The resource type must be `teams`',
        ];

        foreach ($types as $type => $typeMessage) {
            $patchData = $origPatchData;
            $patchData['data']['type'] = $type;

            // make an invalid request for a token
            $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), $team->user);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($typeMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testInvalidNamePatchRequest()
    {
        // create already taken team
        $team = factory(Team::class)->create();
        $otherTeam = factory(Team::class)->create();

        $origPatchData = [
            'data' => [
                'type' => 'teams',
                'id' => $team->id,
                'attributes' => [
                    'paid' => false,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];

        // the emails and expected messages
        $names = [
            null => 'You must specify a name',
            $otherTeam->name => 'The name has already been used',
        ];

        foreach ($names as $name => $nameMessage) {
            $patchData = $origPatchData;
            $patchData['data']['attributes']['name'] = $name;

            // make an invalid request for a token
            $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), $team->user);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($nameMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testSameNamePatchRequest()
    {
        $team = factory(Team::class)->create();

        $patchData = [
            'data' => [
                'type' => 'teams',
                'id' => $team->id,
                'attributes' => [
                    'name' => 'Homygosh',
                    'slug' => str_slug('Homygosh'),
                    'paid' => false,
                ],
                'relationships' => [
                    'user' => [
                        'data' => [
                            'type' => 'users',
                            'id' => $team->user->id,
                        ],
                    ],
                ],
            ],
        ];
        // make invalid request
        $response = $this->callPatch('/api/teams/'.$team->id, json_encode($patchData), $team->user);

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check attributes
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');

        // check the db
        $dbTeam = Team::find($team->id);
        $this->assertEquals($team->paid, $dbTeam->paid, 'paid attribute is updated');
        $this->assertEquals('Homygosh', $dbTeam->name, 'team name is updated');
    }
}
