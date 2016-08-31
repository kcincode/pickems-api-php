<?php

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * @param string $url
     * @param array  $parameters
     *
     * @return Response
     */
    protected function callGet($url, array $parameters = [], $auth = false)
    {
        return $this->call('GET', $url, $parameters, [], [], $this->getServerArray($auth));
    }

    /**
     * @param string $url
     *
     * @return Response
     */
    protected function callDelete($url, $auth = false)
    {
        return $this->call('DELETE', $url, [], [], [], $this->getServerArray($auth));
    }

    /**
     * @param string $url
     * @param string $content
     *
     * @return Response
     */
    protected function callPost($url, $content, $auth = false)
    {
        return $this->call('POST', $url, [], [], [], $this->getServerArray($auth), $content);
    }

    /**
     * @param string $url
     * @param string $content
     *
     * @return Response
     */
    protected function callPatch($url, $content, $auth = false)
    {
        return $this->call('PATCH', $url, [], [], [], $this->getServerArray($auth), $content);
    }

    /**
     * @return array
     */
    public function getServerArray($auth)
    {
        $server = [
            'CONTENT_TYPE' => 'application/vnd.api+json',
        ];

        // required for csrf_token()
        // \Session::start();

        // Here you can choose what auth will be used for testing (basic or jwt)
        $headers = [
            'CONTENT-TYPE' => 'application/vnd.api+json',
            'ACCEPT' => 'application/vnd.api+json',
            'X-Requested-With' => 'XMLHttpRequest',
            // 'X-CSRF-TOKEN' => csrf_token(),
        ];

        // setup token auth if specified
        if ($auth) {
            // get or create an auth user
            $user = User::first();
            if (empty($user)) {
                $user = factory(User::class)->create();
            }
            $token = JWTAuth::fromUser($user);
            $headers['Authorization'] = 'Bearer '.$token;
        }

        foreach ($headers as $key => $value) {
            $server['HTTP_'.$key] = $value;
        }

        return $server;
    }
}
