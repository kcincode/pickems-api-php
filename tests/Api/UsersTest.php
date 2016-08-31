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

    public function testUnauthenticatedDeleteRequest()
    {
        // create a user
        $user = factory(User::class)->create();

        // make unauthenticated request
        $response = $this->call('DELETE', '/api/users/'.$user->id);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidDeleteRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // make invalid request
        $response = $this->call('DELETE', '/api/users/-1', [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedDeleteRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $newUser = factory(User::class)->create();

        // make invalid request
        $response = $this->call('DELETE', '/api/users/'.$newUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidDeleteRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $newUser = factory(User::class)->create();

        // make valid request
        $response = $this->call('DELETE', '/api/users/'.$newUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);
        $this->assertEquals(204, $response->getStatusCode(), 'it has the correct status code');

        // check to make sure the user is no longer in the database
        $this->assertEmpty(User::find($newUser->id), 'the user does not exist in the database');
    }

    public function testUnauthenticatedPatchRequest()
    {
        // create a user
        $user = factory(User::class)->create();

        // make unauthenticated request
        $response = $this->call('PATCH', '/api/users/'.$user->id);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // make invalid request
        $response = $this->call('PATCH', '/api/users/-1', [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(404, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testUnauthorizedPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $otherUser = factory(User::class)->create();

        // make invalid request
        $response = $this->call('PATCH', '/api/users/'.$otherUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token]);

        // check status code
        $this->assertEquals(403, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testAuthorizedAdminPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
        $response = $this->call('PATCH', '/api/users/'.$otherUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        $patchData = [
            'data' => [
                'type' => 'users',
                'id' => $user->id,
                'attributes' => [
                    'name' => 'mod '.$user->name,
                    'email' => 'mod'.$user->email
                ]
            ]
        ];

        // make invalid request
        $response = $this->call('PATCH', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
        $response = $this->call('PATCH', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('The id is required', $errors[0]->title, 'it has the correct error message');
    }

    public function testInvalidTypePatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
            $response = $this->call('PATCH', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

        // create already taken user
        factory(User::class)->create(['email' => 'already@taken.com']);

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
            $response = $this->call('PATCH', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
        $response = $this->call('PATCH', '/api/users/'.$otherUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
        // create a user and get token for the user
        $user = factory(User::class)->create();
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
        $response = $this->call('PATCH', '/api/users/'.$user->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

        // check status code
        $this->assertEquals(422, $response->getStatusCode(), 'it has the correct status code');

        $errors = json_decode($response->content())->errors;
        $this->assertEquals('You must specify a name', $errors[0]->title, 'it has the correct error message');
    }

    public function testValidPasswordPatchRequest()
    {
        // create a user and get token for the user
        $user = factory(User::class)->create(['role' => 'admin']);
        $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);

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
        $response = $this->call('PATCH', '/api/users/'.$otherUser->id, [], [], [], ['HTTP_Authorization' => 'Bearer '.$token], json_encode($patchData));

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
