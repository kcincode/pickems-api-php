<?php

use Pickems\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;

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
        // make a request
        $response = $this->callGet('/api', [], 'user');

        // check for the json payload
        $this->json('GET', '/api')
            ->seeJson(['status' => 'ok']);
    }
}
