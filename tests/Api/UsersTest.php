<?php

use Pickems\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UsersTest extends TestCase
{
    use DatabaseMigrations;
    protected $attrs = ['name', 'email'];

    public function testUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->call('GET', '/api/users');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidGetRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // make an invalid request
        $response = $this->call('GET', '/api/users/-1', [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidGetRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // create another user
        factory(User::class)->create();

        // make a request
        $response = $this->call('GET', '/api/users', [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check the number of data points are correct
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');
        $this->assertEquals(2, count($data->data), 'it has the correct number of records');

        foreach ($data->data as $user) {
            // make single request
            $singleResponse = $this->call('GET', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);
            $this->assertEquals(200, $singleResponse->getStatusCode(), 'it has the correct status code');

            // check for correct info
            $singleData = json_decode($singleResponse->content());
            $this->assertNotEmpty($singleData, 'it has returned some data');

            // check the type and id
            $this->assertEquals('users', $singleData->data->type, 'it has the correct type');
            $this->assertEquals($user->id, $singleData->data->id, 'it has the correct type');

            // check the attributes
            foreach ($this->attrs as $attr) {
                $this->assertEquals($user->attributes->$attr, $singleData->data->attributes->$attr, 'attribute matches whats expected');
            }
        }
    }
}
