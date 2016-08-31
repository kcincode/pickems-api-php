<?php

use Pickems\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class AuthTest extends TestCase
{
    use DatabaseMigrations;

    public function testInvalidTokenRequest()
    {
        // make a request for a token
        $response = $this->call('POST', '/api/token', ['email' => 'testuser@example.com', 'password' => 'testing']);

        // check status code
        $this->assertEquals(401, $response->status());

        // check the data returned
        $data = json_decode($response->content(), true);
        $this->assertEquals($data, ['errors' => [['title' => 'Invalid Credentials', 'code' => 401]]]);
    }

    public function testValidTokenRequest()
    {
        // create user data
        $user = factory(User::class)->create(['password' => bcrypt('testing')]);

        // make a request for a token
        $response = $this->call('POST', '/api/token', ['email' => $user->email, 'password' => 'testing']);

        // check status code
        $this->assertEquals(200, $response->status());

        $data = json_decode($response->content(), true);
        $this->assertTrue(isset($data['access_token']));
    }

    public function testValidRegistrationRequest()
    {
        // make a request for a token
        $response = $this->call('POST', '/api/users', [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'name' => 'Test User',
                    'email' => 'testuser@example.com',
                    'password' => 'testing',
                ],
            ],
        ]);

        // check status code
        $this->assertEquals(204, $response->status());

        // get the data
        $data = json_decode($response->content(), true);

        // check to make sure that user id and object exists
        $this->assertTrue(isset($data['data']['id']));
        $this->assertNotNull(User::find($data['data']['id']));
    }
}
