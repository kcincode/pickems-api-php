<?php

use Pickems\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

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
}
