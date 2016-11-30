<?php

use Pickems\Models\User;
use Pickems\Models\Team;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AdminTeamTest extends TestCase
{
    use DatabaseMigrations;
    protected $attrs = ['name', 'slug', 'paid', 'points', 'playoffs', 'wl'];
    protected $relations = ['user'];

    public function testUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/admin-teams');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidGetRequest()
    {
        // make an invalid request
        $response = $this->callGet('/api/admin-teams/-1', [], 'admin');

        // check status code
        $this->assertEquals(405, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/admin-teams', [], 'user');

        // check status code
        $this->assertEquals(401, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidGetRequest()
    {
        $user = factory(User::class)->create();

        // create 2 admin-teams
        factory(Team::class, 2)->create(['user_id' => $user->id]);

        // create 5 other admin-teams
        factory(Team::class, 5)->create();

        // make a request
        $response = $this->callGet('/api/admin-teams', [], 'admin');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check the number of data points are correct
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');
        $this->assertEquals(7, count($data->data), 'it has the correct number of records');
    }

    public function testUnauthenticatedPatchRequest()
    {
        // create a team
        $team = factory(Team::class)->create();

        // make unauthenticated request
        $response = $this->callPatch('/api/admin-teams/'.$team->id, json_encode([]));

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/admin-teams/-1', json_encode([]), $user);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $team = factory(Team::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/admin-teams/'.$team->id, json_encode([]), $user);

        // check status code
        $this->assertEquals(401, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testAuthorizedAdminPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $team = factory(Team::class)->create(['paid' => false]);

        $patchData = [
            'data' => [
                'type' => 'admin-teams',
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
        $response = $this->callPatch('/api/admin-teams/'.$team->id, json_encode($patchData), 'admin');

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
}
