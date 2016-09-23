<?php

use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflTeam;
use Pickems\Models\NflStat;
use Pickems\Models\TeamPick;
use Pickems\Models\NflPlayer;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class PicksTest extends TestCase
{
    use DatabaseMigrations;

    public function testUnauthenticatedGetRequest()
    {
        // make unauthenticated request
        $response = $this->callGet('/api/picks?team=1&week=1');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMissingTeamPicksRequest()
    {
        // make a request
        $response = $this->callGet('/api/picks?week=1', [], 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMissingWeekPicksRequest()
    {
        // make a request
        $response = $this->callGet('/api/picks?team=1', [], 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidTeamPicksRequest()
    {
        // make a request
        $response = $this->callGet('/api/picks?team=1&week=1', [], 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidWeekPicksRequest()
    {
        // mock a single team
        $team = factory(Team::class)->create();

        // make a request
        $response = $this->callGet('/api/picks?team='.$team->id.'&week=1111', [], 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidPicksRequest()
    {
        // mock a single team
        $team = factory(Team::class)->create();

        // mock some games
        factory(NflGame::class, 5)->create(['week' => 1]);

        // mock week picks
        factory(TeamPick::class)->create(['week' => 1, 'number' => 1]);
        factory(TeamPick::class)->create(['week' => 1, 'number' => 2]);

        // make a request
        $response = $this->callGet('/api/picks?team='.$team->id.'&week=1', [], 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        $data = json_decode($response->content());

        $this->assertTrue(isset($data->schedule) and is_array($data->schedule), 'it has a schedule that is an array');
        $this->assertTrue(isset($data->week) and is_numeric($data->week), 'it has a week and its numeric');

        foreach (['pick1', 'pick2'] as $pickNumber) {
            $this->assertTrue(isset($data->$pickNumber) and is_array((array) $data->$pickNumber), "it has a {$pickNumber} that is an array");
            $this->assertEquals(['selected', 'disabled', 'id', 'type', 'valid', 'reason', 'playmaker'], array_keys((array) $data->$pickNumber), 'it has the required parts in the pick');
        }

        $this->assertTrue(isset($data->picks_left) and is_array((array) $data->picks_left), 'it has a picks_left that is an array');
        $this->assertEquals(['QB', 'RB', 'WRTE', 'K', 'playmakers', 'afc', 'nfc'], array_keys((array) $data->picks_left), 'it has the required parts in the picks_left');

        $this->assertTrue(isset($data->teams_picked) and is_array((array) $data->teams_picked), 'it has a teams_pick that is an array');

        // if the array has data validate the data
        foreach(['afc', 'nfc'] as $conference) {
            foreach ($data->teams_picked->$conference as $item) {
                $this->assertEquals(['abbr', 'name', 'available'], array_keys((array) $item), 'it has the required parts in the teams picked item');
            }
        }
    }

    public function testUnauthenticatedPostRequest()
    {
        // make unauthenticated request
        $response = $this->callPost('/api/picks?team=1&week=1', []);

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMissingTeamPicksPostRequest()
    {
        // make a request
        $response = $this->callPost('/api/picks', json_encode(['week' => 1]), 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testMissingWeekPicksPostRequest()
    {
        // make a request
        $response = $this->callPost('/api/picks', json_encode(['team' => 1]), 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidTeamPicksPostRequest()
    {
        // make a request
        $response = $this->callPost('/api/picks', json_encode(['team' => 1, 'week' => 1]), 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testInvalidWeekPicksPostRequest()
    {
        // mock a single team
        $team = factory(Team::class)->create();

        // make a request
        $response = $this->callPost('/api/picks', json_encode(['team' => $team->id, 'week' => 111]), 'user');

        // check status code
        $this->assertEquals(400, $response->getStatusCode(), 'it has the correct status code');
    }

    public function testValidPicksPostRequest()
    {
        // mock a single team
        $team = factory(Team::class)->create();

        // mock some games
        factory(NflGame::class, 5)->create(['week' => 1]);

        // convert picks to pickdata
        $pick1Data = [
            'id' => factory(NflPlayer::class)->create()->gsis_id,
            'type' => 'player',
            'playmaker' => true,
        ];

        $pick2Data = [
            'id' => factory(NflTeam::class)->create()->abbr,
            'type' => 'team',
            'playmaker' => false,
        ];

        // make a request
        $response = $this->callPost('/api/picks', json_encode(['team' => $team->id, 'week' => 1, 'pick1' => $pick1Data, 'pick2' => $pick2Data]), 'user');

        // check status code
        $this->assertEquals(200, $response->getStatusCode(), 'it has the correct status code');

        $data = json_decode($response->content(), true);
        $this->assertEquals(['status' => 'ok'], $data, 'it has a success status');
    }
}
