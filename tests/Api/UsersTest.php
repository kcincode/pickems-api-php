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
        $response = $this->callGet('/api/users');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidGetRequest()
    {
        // make an invalid request
        $response = $this->callGet('/api/users/-1', [], 'user');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidGetRequest()
    {
        // create 2 user
        factory(User::class, 2)->create();

        // make a request
        $response = $this->callGet('/api/users', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check the number of data points are correct
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');
        $this->assertEquals(3, count($data->data), 'it has the correct number of records');

        foreach ($data->data as $user) {
            // make single request
            $singleResponse = $this->callGet('/api/users/'.$user->id, [], 'user');
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

    public function testUnauthenticatedDeleteRequest()
    {
        // create a user
        $user = factory(User::class)->create();

        // make unauthenticated request
        $response = $this->callDelete('/api/users/'.$user->id);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidDeleteRequest()
    {
        // make invalid request
        $response = $this->callDelete('/api/users/-1', [], 'user');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedDeleteRequest()
    {
        $newUser = factory(User::class)->create();

        // make invalid request
        $response = $this->callDelete('/api/users/'.$newUser->id, 'user');

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidDeleteRequest()
    {
        $newUser = factory(User::class)->create();

        // make valid request
        $response = $this->callDelete('/api/users/'.$newUser->id, 'admin');
        $this->assertEquals(204, $response->getStatusCode(), 'it has the correct status code');

        // check to make sure the user is no longer in the database
        $this->assertEmpty(User::find($newUser->id), 'the user does not exist in the database');
    }

    public function testUnauthenticatedPatchRequest()
    {
        // create a user
        $user = factory(User::class)->create();

        // make unauthenticated request
        $response = $this->callPatch('/api/users/'.$user->id, []);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidPatchRequest()
    {
        // make invalid request
        $response = $this->callPatch('/api/users/-1', [], 'user');

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedPatchRequest()
    {
        $otherUser = factory(User::class)->create();

        // make invalid request
        $response = $this->callPatch('/api/users/'.$otherUser->id, json_encode([]), 'user');

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testAuthorizedAdminPatchRequest()
    {
        $otherUser = factory(User::class)->create();

        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $otherUser->id,
                'attributes' => [
                    'name' => 'mod '.$otherUser->name,
                    'email' => 'mod'.$otherUser->email
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$otherUser->id, json_encode($patchData), 'admin');

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
        $user = factory(User::class)->create();
        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $user->id,
                'attributes' => [
                    'name' => 'Modified User',
                    'email' => 'modemail@example.com',
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$user->id, json_encode($patchData), $user);

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
        $user = factory(User::class)->create();

        $patchData = [
            'data' => [
                'type' => 'users',
                'attributes' => [
                    'name' => 'mod '.$user->name,
                    'email' => 'mod'.$user->email
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$user->id, json_encode($patchData), $user);

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('The id is required', $errors[0]->title, 'it has the correct error message');
    }

    public function testInvalidTypePatchRequest()
    {
        $user = factory(User::class)->create();
        $origPatchData = [
            'data' => [
                'id' => $user->id,
                'attributes' => [
                    'name' => 'mod '.$user->name,
                    'email' => 'mod'.$user->email
                ]
            ]
        ];

        // the types and expected messages
        $types = [
            null => 'The resource type is required',
            'bleh' => 'The resource type must be `users`',
        ];

        foreach ($types as $type => $typeMessage) {
            $patchData = $origPatchData;
            $patchData['data']['type'] = $type;

            // make an invalid request for a token
            $response = $this->callPatch('/api/users/'.$user->id, json_encode($patchData), $user);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($typeMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testInvalidEmailPatchRequest()
    {
        // create already taken user
        factory(User::class)->create(['email' => 'already@taken.com']);
        $user = factory(User::class)->create();

        $origPatchData = [
            'data' => [
                'type' => 'users',
                'id' => $user->id,
                'attributes' => [
                    'name' => 'mod '.$user->name,
                ]
            ]
        ];

        // the emails and expected messages
        $emails = [
            null => 'You must enter an email',
            'notvalid' => 'You must enter a valid email',
            'already@taken.com' => 'The email has already been used',
        ];

        foreach ($emails as $email => $emailMessage) {
            $patchData = $origPatchData;
            $patchData['data']['attributes']['email'] = $email;

            // make an invalid request for a token
            $response = $this->callPatch('/api/users/'.$user->id, json_encode($patchData), $user);

            // check status code
            $this->assertEquals(422, $response->status(), 'it returns a 422 status');

            // check the error message
            $errors = json_decode($response->content())->errors;
            $this->assertEquals($emailMessage, $errors[0]->title, 'it has the correct error message');
            $this->assertEquals(422, $errors[0]->status, 'it has the correct status code for the error message');
        }
    }

    public function testSameEmailPatchRequest()
    {
        $otherUser = factory(User::class)->create();

        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $otherUser->id,
                'attributes' => [
                    'name' => 'mod '.$otherUser->name,
                    'email' => $otherUser->email,
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$otherUser->id, json_encode($patchData), 'admin');

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

    public function testInvalidNamePatchRequest()
    {
        $user = factory(User::class)->create();
        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $user->id,
                'attributes' => [
                    'email' => 'mod'.$user->email
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$user->id, json_encode($patchData), $user);

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('You must specify a name', $errors[0]->title, 'it has the correct error message');
    }

    public function testValidPasswordPatchRequest()
    {
        $otherUser = factory(User::class)->create();

        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $otherUser->id,
                'attributes' => [
                    'name' => 'mod '.$otherUser->name,
                    'email' => $otherUser->email,
                    'password' => 'testing2',
                ]
            ]
        ];

        // make invalid request
        $response = $this->callPatch('/api/users/'.$otherUser->id, json_encode($patchData), 'admin');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        // check attributes
        $data = json_decode($response->content());
        $this->assertNotEmpty($data, 'it has returned some data');

        // check the password attribute
        $dbUser = User::find($otherUser->id);
        $this->assertNotEmpty($dbUser, 'it has returned some data');
        $this->assertTrue($dbUser->password != 'testing2' && $dbUser->password != $otherUser->password, 'it updated and hashed the password');
    }
}
