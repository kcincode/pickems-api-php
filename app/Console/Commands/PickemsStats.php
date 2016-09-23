<?php

namespace Pickems\Console\Commands;

use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflTeam;
use Pickems\Models\NflStat;
use Pickems\Models\BestPick;
use Pickems\Models\TeamPick;
use Pickems\Models\MostPicked;
use Illuminate\Console\Command;
use Pickems\Models\WeeklyLeader;
use Illuminate\Support\Facades\DB;

class PickemsStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickems:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all the stats for easy display';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get ther current week
        list($type, $week) = explode('-', NflGame::currentWeek());

        if ($type == 'REG' && $week >= 2) {
            $this->weeklyLeaders($type, $week - 1);
            // $this->teamsWinLoss($type, $week - 1);
            // $this->teamScores($type, $week - 1);
            // $this->bestPicks($type, $week - 1);
            // $this->mostPicked($type, $week - 1);
        }
    }

    private function weeklyLeaders($type, $toWeek)
    {
        $this->info("Calculating weekly leaders:");
        if ($type == 'POST') {
            $toWeek = 17;
        }

        // clear the table
        DB::table('weekly_leaders')->truncate();

        $bar = $this->output->createProgressBar($toWeek);
        $start = microtime(true);
        foreach (range(1, $toWeek) as $week) {
            // fetch all team points for week
            $teamPicks = TeamPick::where('week', '=', $week)
                ->orderBy('picked_at', 'asc')
                ->get();

            $weekData = [];
            foreach($teamPicks as $teamPick) {
                if (isset($weekData[$teamPick->team_id])) {
                    // if set just update points
                    $weekData[$teamPick->team_id]['points'] += $teamPick->points();
                } else {
                    $weekData[$teamPick->team_id] = [
                        'week' => $week,
                        'team_id' => $teamPick->team_id,
                        'team' => $teamPick->team->name,
                        'points' => $teamPick->points(),
                    ];
                }
            }

            // sort by points
            usort($weekData, function($a, $b) {
                return ($a['points'] >= $b['points']) ? -1 : 1;
            });

            // pick top one.
            if (count($weekData)) {
                WeeklyLeader::create($weekData[0]);
            }

            $bar->advance();
        }
        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time. ' seconds');

        if ($type == 'POST') {
            // TODO: Playoff weekly leaders
        }
    }

    private function teamsWinLoss($type, $toWeek)
    {
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $teams = Team::all();
        $bar = $this->output->createProgressBar(count($teams) + 3);
        $start = microtime(true);

        $games = NflGame::where('week', '<=', $toWeek)
            ->get();
        $bar->advance();


        $teams = NflTeam::all()->pluck('abbr')->toArray();
        $zeros = array_fill(0, count($teams), ['wins' => 0, 'losses' => 0]);
        $teamsWinLoss = array_combine($teams, $zeros);
        $bar->advance();

        // set each teams wins and losses
        foreach($games as $game) {
            $teamsWinLoss[$game->winning_team_id]['wins']++;
            $teamsWinLoss[$game->losing_team_id]['losses']++;
        }
        $bar->advance();

        // apply win/loss to teams
        foreach($teams as $team) {
            $pickedTeams = [];

            // fetch teams used
            foreach($team->teamPicks as $teamPick) {
                if ($teamPick->nfl_stat->player && $teamPick->week <= $toWeek) {
                    $pickedTeams[] = $teamPick->nfl_stat->player->team_id;
                }
            }

            // fetch w/l on teams remaining
            $wlData = ['wins' => 0, 'losses' => 0];
            foreach($teamsWinLoss as $abbr => $data) {
                if (!in_array($abbr, $pickedTeams)) {
                    $wlData['wins'] += $data['wins'];
                    $wlData['losses'] += $data['losses'];
                }
            }

            // set the w/l ratio
            $team->wl = number_format($wlData['wins'] / $wlData['losses'], 3);
            $team->save();
            $bar->advance();
        }

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time. ' seconds');
    }

    private function teamScores($type, $toWeek)
    {
        if ($type == 'POST') {
            $toWeek = 17;
        }

        // apply win/loss to teams
        foreach(Team::all() as $team) {
            $points = 0;
            // fetch teams used
            foreach($team->teamPicks as $teamPick) {
                // only calculate points for valid picks
                if ($teamPick->valid && $teamPick->week <= $toWeek) {
                    $points += $teamPick->points();
                }
            }

            // update the points
            $team->points = $points;

            $playoffPoints = 0;
            if ($type == 'POST') {
                // TODO: Playoff picks
            }

            $team->playoffs = $playoffPoints;
            $team->save();
        }
    }

    private function bestPicks($type, $toWeek)
    {
        if ($type == 'POST') {
            $toWeek = 17;
        }

        // clear the table
        DB::table('best_picks')->truncate();

        $stats = NflStat::where('week', '<=',  $toWeek)
            ->get();

        $allStats = [];
        foreach($stats as $stat) {
            $allStats[] = [
                'week' => $stat->week,
                'pick' => ($stat->player_id) ? $stat->player->display() : $stat->team->display(),
                'position' => ($stat->player_id) ? $stat->player->position : null,
                'team' => ($stat->player_id) ? $stat->player->team->abbr : null,
                'conference' => ($stat->team_id) ? $stat->team->conference : null,
                'points' => $stat->points(),
                'playmaker' => false,
            ];
        }

        // sort by points
        usort($allStats, function($a, $b) {
            return ($a['points'] > $b['points']) ? -1 : 1;
        });

        $picks = [];
        $picksLeft = [
            'QB' => 8,
            'RB' => 8,
            'WRTE' => 8,
            'K' => 8,
            'playmakers' => 2,
            'AFC' => 1,
            'NFC' => 1,
            'teams' => [],
        ];

        // genereate picks
        foreach($allStats as $stat) {
            if (isset($picks[$stat['week']]) && count($picks[$stat['week']]) == 2) {
                // already have 2 picks
                continue;
            } else if (!isset($picks[$stat['week']])) {
                // initialize week
                $picks[$stat['week']] = [];
            }

            if (!empty($stat['team'])) {
                // player valid pick
                if ($picksLeft[$stat['position']] > 0 && !in_array($stat['team'], $picksLeft['teams'])){
                    // playmaker?
                    if ($picksLeft['playmakers'] > 0) {
                        $picksLeft['playmakers']--;
                        $stat['points'] *= 2;
                        $stat['playmaker'] = true;
                    }

                    // update the counts
                    $picksLeft[$stat['position']]--;
                    $picksLeft['teams'][] = $stat['team'];

                    // set pick
                    $picks[$stat['week']][] = $stat;
                }
            } else {
                // team pick
                if ($picksLeft[$stat['conference']] > 0) {
                    // update the counts
                    $picksLeft[$stat['conference']]--;

                    // set pick
                    $picks[$stat['week']][] = $stat;
                }
            }
        }

        foreach($picks as $week => $pick) {
            BestPick::create([
                'type' => 'REG',
                'week' => $week,
                'pick1' => $pick[0]['pick'],
                'pick1_points' => $pick[0]['points'],
                'pick1_playmaker' => $pick[0]['playmaker'],
                'pick2' => $pick[1]['pick'],
                'pick2_points' => $pick[1]['points'],
                'pick2_playmaker' => $pick[1]['playmaker'],
            ]);
        }

        if ($type == 'POST') {
            // TODO: Playoff best picks
        }
    }

    private function mostPicked($type, $toWeek)
    {
        if ($type == 'POST') {
            $toWeek = 17;
        }

        // clear the table
        DB::table('most_picked')->truncate();
        $teamPicks = TeamPick::where('week', '<=', $toWeek)
            ->get();

        $picked = [];
        foreach($teamPicks as $teamPick) {
            if ($teamPick->nfl_stat->player_id) {
                $type = 'player';
                $key = $teamPick->nfl_stat->player_id;
                $name = $teamPick->nfl_stat->player->display();
            } else {
                $type = 'team';
                $key = $teamPick->nfl_stat->team_id;
                $name = $teamPick->nfl_stat->team->display();
            }

            if (!isset($picked[$teamPick->week][$key])) {
                $picked[$teamPick->week][$key] = [
                    'name' => $name,
                    'numpicked' => 1,
                ];
            } else {
                $picked[$teamPick->week][$key]['numpicked'] += 1;
            }
        }

        // genereate text with all picks > 1
        $mostPicked = [];
        foreach ($picked as $week => $data) {
            usort($data, function($a, $b) {
                return ($a['numpicked'] > $b['numpicked']) ? -1 : 1;
            });

            foreach($data as $item) {
                if ($item['numpicked'] < 2) {
                    break;
                }

                MostPicked::create([
                    'week' => $week,
                    'name' => $item['name'],
                    'number_picked' => $item['numpicked']
                ]);
            }
        }

        if ($type == 'POST') {
            // TODO: Most picked in playoffs
        }
    }
}
