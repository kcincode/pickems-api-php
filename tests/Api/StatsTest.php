<?php

use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflTeam;
use Pickems\Models\NflStat;
use Pickems\Models\TeamPick;
use Pickems\Models\NflPlayer;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class StatsTest extends TestCase
{
    use DatabaseMigrations;

    public function testWeeklyUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/weekly');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testWeeklyGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/weekly', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testBestUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/best');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testBestGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/best', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMostUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/most');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMostGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/most', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testRankingUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/ranking');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testRankingGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/stats/ranking', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');
    }

}
