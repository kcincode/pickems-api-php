<?php

namespace Pickems;

use DateTime;

class NflScrape
{
    protected $year;

    protected $teamsUrl = 'http://feeds.nfl.com/feeds-rs/teams/:year:.json';
    protected $gamesUrl = 'http://www.nfl.com/ajax/scorestrip?season=:year:&seasonType=:type:&week=:week:';

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
            $nflData = file_get_contents(str_replace(':year:', $this->year, $this->teamsUrl));

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
                    $url = str_replace(':year:', $this->year, $this->gamesUrl);
                    $url = str_replace(':type:', $type, $url);
                    $url = str_replace(':week:', $week, $url);

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

    }
}
