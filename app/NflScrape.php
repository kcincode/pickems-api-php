<?php

namespace Pickems;

use DateTime;
use Pickems\Models\NflTeam;
use Pickems\Models\NflGame;
use Symfony\Component\DomCrawler\Crawler;

class NflScrape
{
    protected $year;

    protected $teamsUrl = 'http://feeds.nfl.com/feeds-rs/teams/%s.json';
    protected $gamesUrl = 'http://www.nfl.com/ajax/scorestrip?season=%s&seasonType=%s&week=%s';
    protected $rosterUrl = 'http://www.nfl.com/teams/roster?team=%s';
    protected $profileUrl = 'http://www.nfl.com/players/profile?id=%s';
    protected $statsUrl = 'http://www.nfl.com/liveupdate/game-center/%s/%s_gtd.json?random=%s';

    public function __construct($year)
    {
        $this->year = $year;
    }

    public function fetchTeams()
    {
        // setup the cache file location
        $teamCache = storage_path().'/data/teams/'.$this->year.'.json.gz';

        if (file_exists($teamCache)) {
            // fetch data from cache file
            $data = json_decode(gzuncompress(file_get_contents($teamCache)));
        } else {
            // fetch data from nfl.com
            $nflData = file_get_contents(sprintf($this->teamsUrl, $this->year));

            // write to cache file
            file_put_contents($teamCache, gzcompress($nflData, 9));

            // decode the data
            $data = json_decode($nflData);
        }

        // filter out the not actual teams
        return array_map(function ($team) {
            return [
                'abbr' => $team->abbr,
                'city' => $team->cityState,
                'name' => $team->nick,
                'conference' => $team->conferenceAbbr,
            ];
        }, array_filter($data->teams, function ($team) {
            return $team->teamType == 'TEAM';
        }));
    }

    public function fetchGames()
    {
        $data = [
            'REG' => range(1, 17),
            'POST' => range(1, 4),
        ];

        $results = [];

        foreach ($data as $type => $weeks) {
            foreach ($weeks as $week) {
                // define the cache location
                $gameCache = storage_path().'/data/games/'.$this->year.'-'.$type.'-'.str_pad($week, 2, '0', STR_PAD_LEFT).'.json.gz';

                if (file_exists($gameCache)) {
                    // load from cache
                    $data = json_decode(gzuncompress(file_get_contents($gameCache)));
                } else {
                    // build the url
                    $url = sprintf($this->gamesUrl, $this->year, $type, $week);

                    // load the file and convert
                    $xmlString = file_get_contents($url);
                    $xmlString = str_replace(["\n", "\r", "\t"], '', $xmlString);
                    $xmlString = trim(str_replace('"', "'", $xmlString));

                    // load json data
                    $data = json_decode(json_encode(simplexml_load_string($xmlString)));
                }

                // make sure we have data for that week
                if (empty($data->gms->g)) {
                    continue;
                }

                // add each game to the results
                foreach ($data->gms->g as $game) {
                    // convert date time
                    $string = sprintf(
                        '%s %s PM',
                        substr($game->{'@attributes'}->eid, 0, -2),
                        $game->{'@attributes'}->t,
                        $game->{'@attributes'}->q
                    );
                    $datetime = DateTime::createFromFormat('Ymd g:i A', $string);

                    // check for winning losing team
                    $homeScore = $game->{'@attributes'}->hs;
                    $awayScore = $game->{'@attributes'}->vs;
                    $winningTeam = null;
                    $losingTeam = null;

                    // if there is a score and its not tied
                    if (is_numeric($homeScore) && is_numeric($awayScore) && $homeScore != $awayScore) {
                        $winningTeam = ($homeScore > $awayScore) ? $game->{'@attributes'}->h : $game->{'@attributes'}->v;
                        $losingTeam = ($homeScore > $awayScore) ? $game->{'@attributes'}->v : $game->{'@attributes'}->h;
                    }

                    $results[] = [
                        'starts_at' => $datetime,
                        'week' => $week,
                        'type' => $type,
                        'eid' => $game->{'@attributes'}->eid,
                        'gsis' => $game->{'@attributes'}->gsis,
                        'home_team_id' => $game->{'@attributes'}->h,
                        'away_team_id' => $game->{'@attributes'}->v,
                        'winning_team_id' => $winningTeam,
                        'losing_team_id' => $losingTeam,
                    ];
                }

                $now = new DateTime();
                if ($now > $datetime) {
                    // cache the file
                    file_put_contents($gameCache, gzcompress(json_encode($data), 9));
                }
            }
        }

        return $results;
    }

    public function fetchPlayers()
    {
        $playerLookupFile = storage_path().'/data/players/lookup.json.gz';
        $cachedPlayers = json_decode(gzuncompress(file_get_contents($playerLookupFile)), true);

        $players = [];

        foreach (NflTeam::all() as $nflTeam) {
            $url = sprintf($this->rosterUrl, $nflTeam->abbr);
            $crawler = new Crawler();
            $crawler->addContent(file_get_contents($url));

            foreach ($crawler->filter('table[id=result] tbody tr') as $row) {
                $matches = [];
                preg_match('/^([\d ]+)\s+([ A-Za-z\.\'-]+),\s+([ A-Za-z\.\'-]+)\s+([RB|FB|QB|K|WR|TE]+)\s+([ACT]+)/', str_replace("\n", ' ', $row->nodeValue), $matches);

                if (count($matches)) {
                    $profileMatches = [];
                    if (!preg_match('/(\d+)/', $row->childNodes[2]->childNodes[1]->getAttribute('href'), $profileMatches)) {
                        throw new \Exception('ERROR: Cannot find profile ID');
                    }

                    // check to see if the player is cached
                    if (array_key_exists($profileMatches[1], $cachedPlayers)) {
                        // get the gsis_id
                        $gsis_id = $cachedPlayers[$profileMatches[1]]['gsis_id'];
                    } else {
                        // make request to profile page to get player gsis and add it to cache
                        $data = $this->fetchGsisFromProfile($profileMatches[1], $matches[3].' '.$matches[2]);

                        // cache the data and set the gsis_id
                        $cachedPlayers[$profileMatches[1]] = $data;
                        $gsis_id = $data['gsis_id'];
                    }

                    $players[] = [
                        'team_id' => $nflTeam->abbr,
                        'name' => $matches[3].' '.$matches[2],
                        'position' => $this->convertPosition($matches[4]),
                        'profile_id' => $profileMatches[1],
                        'gsis_id' => $gsis_id,
                        'active' => true,
                    ];
                }
            }
        }

        // cache the players file
        file_put_contents($playerLookupFile, gzcompress(json_encode($cachedPlayers), 9));

        return $players;
    }

    private function fetchGsisFromProfile($profileId, $name)
    {
        // setup the data
        $data = ['profile_id' => $profileId, 'name' => $name];

        // fetch the profile url
        $url = sprintf($this->profileUrl, $profileId);
        $profile = file_get_contents($url);

        // find the gsis id
        $matches = [];
        $data['gsis_id'] = (preg_match('/GSIS ID: (\d\d-\d\d\d\d\d\d\d)/', $profile, $matches)) ? $matches[1] : null;

        // return the data
        return $data;
    }

    private function convertPosition($position)
    {
        switch($position) {
            case 'RB':
            case 'FB':
                return 'RB';
            case 'WR':
            case 'TE':
                return 'WRTE';
            default:
                return $position;
        }
    }

    public function fetchStats($week)
    {
        $results = [];
        foreach (NflGame::where('week', '=', $week)->get() as $game) {
            $gameId = $game->eid;
            $url = sprintf($this->statsUrl, $gameId, $gameId, microtime(true));
            try {
                $page = json_decode(file_get_contents($url));
            } catch(\Exception $e) {
                continue;
            }

            $data = [];
            if (!empty($page->$gameId)) {
                $data['eid'] = $gameId;
                $data['home_team_id'] = $page->$gameId->home->abbr;
                $data['away_team_id'] = $page->$gameId->away->abbr;
                $data['homescore'] = $page->$gameId->home->score->T;
                $data['awayscore'] = $page->$gameId->away->score->T;
                $data['homediff'] = $page->$gameId->home->score->T - $page->$gameId->away->score->T;
                $data['awaydiff'] = $page->$gameId->away->score->T - $page->$gameId->home->score->T;

                if ($data['homescore'] != $data['awayscore']) {
                    $data['winning_team_id'] = ($data['homescore'] > $data['awayscore']) ? $data['home_team_id'] : $data['away_team_id'];
                    $data['losing_team_id'] = ($data['homescore'] > $data['awayscore']) ? $data['away_team_id'] : $data['home_team_id'];
                }

                foreach (['home_team_id' => $page->$gameId->home->stats, 'away_team_id' => $page->$gameId->away->stats] as $team => $stats) {
                    foreach (['passing', 'rushing', 'receiving', 'kicking', 'kickret', 'puntret'] as $type) {
                        if (isset($stats->$type)) {
                            foreach ($stats->$type as $id => $player) {
                                if (isset($data['stats'][$id])) {
                                    $data['stats'][$id]['td'] += (isset($player->tds)) ? $player->tds : 0;
                                    $data['stats'][$id]['two'] += (isset($player->twoptm)) ? $player->twoptm : 0;
                                    $data['stats'][$id]['fg'] += (isset($player->fgm)) ? $player->fgm : 0;
                                    $data['stats'][$id]['xp'] += (isset($player->xpmade)) ? $player->xpmade : 0;
                                } else {
                                    $data['stats'][$id] = [
                                        'td' => (isset($player->tds)) ? $player->tds : 0,
                                        'two' => (isset($player->twoptm)) ? $player->twoptm : 0,
                                        'fg' => (isset($player->fgm)) ? $player->fgm : 0,
                                        'xp' => (isset($player->xpmade)) ? $player->xpmade : 0,
                                    ];
                                }
                            }
                        }
                    }
                }

                $results[$game->game_id] = $data;
            }
        }

        return $results;
    }
}
