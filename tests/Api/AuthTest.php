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
        $this->assertEquals(201, $response->status(), 'it returns a 201 status');

        // get the data
        $data = json_decode($response->content(), true);

        // check to make sure that user id and object exists
        $this->assertTrue(isset($data['data']['id']), 'the response has an id');
        $user = User::find($data['data']['id']);
        $this->assertNotNull($user, 'the user object exists in the database');
        $this->assertFalse('testing' == $user->password, 'the users password is not in plain text');
    }

    public function testInvalidTypeRegistrationRequest()
    {
        $types = [
            null => 'The resource type is required',
            'bleh' => 'The resource type must be `users`',
        ];

        foreach ($types as $type => $typeMessage) {
            // make an invalid request for a token
            $response = $this->call('POST', '/api/users', [
                'data' => [
                    'type' => $type,
                    'attributes' => [
                        'name' => 'Test User',
                        'email' => 'testuser@example.com',
                        'password' => 'testing',
                    ],
                ],
            ]);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($typeMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct code for the error message');
        }
    }

    public function testInvalidEmailRegistrationRequest()
    {
        // create the expected user
        factory(User::class)->create(['email' => 'already@taken.com']);

        // the emails and expected messages
        $emails = [
            null => 'You must enter an email',
            'notvalid' => 'You must enter a valid email',
            'already@taken.com' => 'The email has already been used',
        ];

        foreach ($emails as $email => $emailMessage) {
            // make an invalid request for a token
            $response = $this->call('POST', '/api/users', [
                'data' => [
                    'type' => 'users',
                    'attributes' => [
                        'name' => 'Test User',
                        'email' => $email,
                        'password' => 'testing',
                    ],
                ],
            ]);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($emailMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testInvalidNameRegistrationRequest()
    {
        // make an invalid request for a token
        $response = $this->call('POST', '/api/users', [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'email' => 'testuser@example.com',
                    'password' => 'testing',
                ],
            ],
        ]);

        // check status code
        $this->assertEquals(422, $response->status(), 'it returns a 422 status');

        // check the error message
        $errors = json_decode($response->content())->errors;
        $this->assertEquals('You must specify a name', $errors[0]->title, 'it has the correct error message');
        $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
    }

    public function testInvalidPasswordRegistrationRequest()
    {
        // make an invalid request for a token
        $response = $this->call('POST', '/api/users', [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'email' => 'testuser@example.com',
                    'name' => 'Test User',
                ],
            ],
        ]);

        // check status code
        $this->assertEquals(422, $response->status(), 'it returns a 422 status');

        // check the error message
        $errors = json_decode($response->content())->errors;
        $this->assertEquals('You must enter a password', $errors[0]->title, 'it has the correct error message');
        $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
    }
}
