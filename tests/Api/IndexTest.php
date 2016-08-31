<?php

use Pickems\Models\User;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class IndexTest extends TestCase
{
    use DatabaseMigrations;

    public function testUnauthorizedRequest()
    {
        $this->json('GET', '/api')
            ->seeJson(['error' => 'token_not_provided']);
    }

    public function testAuthorizedRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // make a request
        $response = $this->call('GET', '/api', [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check for the json payload
        $this->json('GET', '/api')
            ->seeJson(['status' => 'ok']);
    }
}
