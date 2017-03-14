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
use Pickems\Models\TeamPlayoffPick;

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

        if ($type == 'REG' && $week >= 2 || $type == 'POST') {
            // $this->weeklyLeaders($type, $week - 1);
            // $this->teamsWinLoss($type, $week - 1);
            // $this->teamScores($type, $week - 1);
            // $this->teamPlayoffScores();
            // $this->bestPicks($type, $week - 1);
            // $this->mostPicked($type, $week - 1);
            if ($type == 'POST') {
                $this->playoffScores($week - 1);
            }
        }
    }

    private function weeklyLeaders($type, $toWeek)
    {
        $this->info('Calculating weekly leaders:');
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
            foreach ($teamPicks as $teamPick) {
                if (isset($weekData[$teamPick->team_id])) {
                    // if set just update points
                    $weekData[$teamPick->team_id]['points'] += $teamPick->points();
                } else {
                    $weekData[$teamPick->team_id] = [
                        'week' => $week,
                        'team_id' => $teamPick->team_id,
                        'points' => $teamPick->points(),
                    ];
                }
            }

            // sort by points
            usort($weekData, function ($a, $b) {
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
        $this->info('    '.$time.' seconds');

        if ($type == 'POST') {
            // TODO: Playoff weekly leaders
        }
    }

    private function teamsWinLoss($type, $toWeek)
    {
        $this->info('Calculating team win loss ratios:');
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $teams = NflTeam::all()->pluck('abbr')->toArray();
        $bar = $this->output->createProgressBar(count($teams) + 3);
        $start = microtime(true);

        $games = NflGame::where('week', '<=', $toWeek)
            ->get();
        $bar->advance();

        $zeros = array_fill(0, count($teams), ['wins' => 0, 'losses' => 0]);
        $teamsWinLoss = array_combine($teams, $zeros);
        $bar->advance();

        // set each teams wins and losses
        foreach ($games as $game) {
            if ($game->winning_team_id) {
                ++$teamsWinLoss[$game->winning_team_id]['wins'];
            }

            if ($game->losing_team_id) {
                ++$teamsWinLoss[$game->losing_team_id]['losses'];
            }
        }
        $bar->advance();

        // apply win/loss to teams
        foreach (Team::all() as $team) {
            $pickedTeams = [];

            // fetch teams used
            foreach ($team->teamPicks as $teamPick) {
                if ($teamPick->nfl_stat->player && $teamPick->week <= $toWeek) {
                    $pickedTeams[] = $teamPick->nfl_stat->player->team_id;
                }
            }

            // fetch w/l on teams remaining
            $wlData = ['wins' => 0, 'losses' => 0];
            foreach ($teamsWinLoss as $abbr => $data) {
                if (!in_array($abbr, $pickedTeams)) {
                    $wlData['wins'] += $data['wins'];
                    $wlData['losses'] += $data['losses'];
                }
            }

            // set the w/l ratio
            if (in_array(0, array_values($wlData))) {
                $team->wl = '0.000';
            } else {
                $team->wl = number_format($wlData['wins'] / $wlData['losses'], 3);
            }

            $team->save();
            $bar->advance();
        }

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function teamScores($type, $toWeek)
    {
        $this->info('Calculating team scores:');
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $teams = Team::all();
        $bar = $this->output->createProgressBar(count($teams));
        $start = microtime(true);

        // apply win/loss to teams
        foreach ($teams as $team) {
            $points = 0;
            // fetch teams used
            foreach ($team->teamPicks as $teamPick) {
                // only calculate points for valid picks
                if ($teamPick->valid && $teamPick->week <= $toWeek) {
                    $points += $teamPick->points();
                }
            }

            // update the points
            $team->points = $points;

            $team->playoffs = 0;
            $team->save();
            $bar->advance();
        }

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function teamPlayoffScores()
    {
        $this->info('Calculating team playoff scores:');
        $start = microtime(true);

        $data = [
            'gold' => [],
            'silver' => [],
            'bronze' => Team::where('paid', '=', false)
                ->orderBy('points', 'desc')
                ->orderBy('wl', 'desc')
                ->get(),
        ];

        $paidTeams = Team::where('paid', '=', true)
            ->orderBy('points', 'desc')
            ->orderBy('wl', 'desc')
            ->get();

        $bar = $this->output->createProgressBar(count($paidTeams) + 3);

        $teamsCount = count($paidTeams);
        $bar->advance();
        $half = ceil(count($paidTeams) / 2);
        $bar->advance();

        foreach ($paidTeams as $idx => $team) {
            if ($idx < $half) {
                $data['gold'][] = $team;
            } else {
                $data['silver'][] = $team;
            }
            $bar->advance();
        }

        // calculate the team playoff points
        foreach ($data as $type => $teams) {
            $pts = (count($teams) - 1) * 6;
            foreach ($teams as $team) {
                // update the playoff picks starting points
                $team->playoffs = $pts;
                $team->save();

                // ensure that a playoff picks record is created
                TeamPlayoffPick::fetchOrCreate($team->id);

                // update the points
                $pts -= 6;
            }
        }
        $bar->advance();


        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function bestPicks($type, $toWeek)
    {
        $this->info('Calculating best picks:');
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $stats = NflStat::where('week', '<=',  $toWeek)
            ->get();

        $bar = $this->output->createProgressBar((count($stats) * 2) + 3);
        $start = microtime(true);

        // clear the table
        DB::table('best_picks')->truncate();

        $allStats = [];
        foreach ($stats as $stat) {
            $allStats[] = [
                'type' => 'REG',
                'week' => $stat->week,
                'pick' => ($stat->player_id) ? $stat->player->display() : $stat->team->display(),
                'position' => ($stat->player_id) ? $stat->player->position : null,
                'team' => ($stat->player_id) ? $stat->player->team->abbr : null,
                'conference' => ($stat->team_id) ? $stat->team->conference : null,
                'points' => $stat->points(),
                'playmaker' => false,
            ];

            $bar->advance();
        }

        // sort by points
        usort($allStats, function ($a, $b) {
            return ($a['points'] > $b['points']) ? -1 : 1;
        });
        $bar->advance();

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
        foreach ($allStats as $stat) {
            if (isset($picks[$stat['week']]) && count($picks[$stat['week']]) == 2) {
                // already have 2 picks
                continue;
            } elseif (!isset($picks[$stat['week']])) {
                // initialize week
                $picks[$stat['week']] = [];
            }

            if (!empty($stat['team'])) {
                // player valid pick
                if ($picksLeft[$stat['position']] > 0 && !in_array($stat['team'], $picksLeft['teams'])) {
                    // playmaker?
                    if ($picksLeft['playmakers'] > 0) {
                        --$picksLeft['playmakers'];
                        $stat['points'] *= 2;
                        $stat['playmaker'] = true;
                    }

                    // update the counts
                    --$picksLeft[$stat['position']];
                    $picksLeft['teams'][] = $stat['team'];

                    // set pick
                    $picks[$stat['week']][] = $stat;
                }
            } else {
                // team pick
                if ($picksLeft[$stat['conference']] > 0) {
                    // update the counts
                    --$picksLeft[$stat['conference']];

                    // set pick
                    $picks[$stat['week']][] = $stat;
                }
            }
            $bar->advance();
        }

        $total = 0;
        foreach ($picks as $week => $pick) {
            BestPick::create([
                'type' => $pick[0]['type'],
                'week' => $week,
                'pick1' => $pick[0]['pick'],
                'pick1_points' => $pick[0]['points'],
                'pick1_playmaker' => $pick[0]['playmaker'],
                'pick2' => $pick[1]['pick'],
                'pick2_points' => $pick[1]['points'],
                'pick2_playmaker' => $pick[1]['playmaker'],
                'total' => $pick[0]['points'] + $pick[1]['points'],
            ]);

            $total += $pick[0]['points'] + $pick[1]['points'];
        }
        $bar->advance();

        BestPick::create([
            'type' => 'REG',
            'week' => 18,
            'pick1' => '',
            'pick1_points' => 0,
            'pick1_playmaker' => false,
            'pick2' => '',
            'pick2_points' => 0,
            'pick2_playmaker' => false,
            'total' => $total,
        ]);
        $bar->advance();

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function bestPlayoffPicks()
    {
        $this->info('Calculating best playoff picks:');
        $bar = $this->output->createProgressBar(0);
        $start = microtime(true);
        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function playoffScores($week)
    {
        $teamPlayoffPicks = TeamPlayoffPick::all();

        $this->info('Calculating playoff scores:');
        $bar = $this->output->createProgressBar($teamPlayoffPicks->count());
        $start = microtime(true);

        $points = [];
        foreach($teamPlayoffPicks as $playoffPick) {
            $team = $playoffPick->team;
            $team->playoff_points = $playoffPick->points();
            $team->save();
            $bar->advance();
        }

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');
    }

    private function mostPicked($type, $toWeek)
    {
        $this->info('Calculating the most picked:');
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $teamPicks = TeamPick::where('week', '<=', $toWeek)
            ->get();
        $bar = $this->output->createProgressBar(count($teamPicks) * 2);
        $start = microtime(true);

        // clear the table
        DB::table('most_picked')->truncate();

        $picked = [];
        foreach ($teamPicks as $teamPick) {
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
            $bar->advance();
        }

        // genereate text with all picks > 1
        $mostPicked = [];
        foreach ($picked as $week => $data) {
            usort($data, function ($a, $b) {
                return ($a['numpicked'] > $b['numpicked']) ? -1 : 1;
            });

            foreach ($data as $item) {
                if ($item['numpicked'] < 2) {
                    break;
                }

                MostPicked::create([
                    'type' => 'REG',
                    'week' => $week,
                    'name' => $item['name'],
                    'number_picked' => $item['numpicked'],
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time.' seconds');

        if ($type == 'POST') {
            // TODO: Most picked in playoffs
        }
    }
}
