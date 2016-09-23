<?php

namespace Pickems\Console\Commands;

use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflTeam;
use Pickems\Models\TeamPick;
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
            $this->teamsWinLoss($type, $week - 1);
            $this->teamScores($type, $week - 1);
            $this->bestPicks($type, $week - 1);
        }
    }

    private function weeklyLeaders($type, $toWeek)
    {
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
    }

    private function teamsWinLoss($type, $toWeek)
    {
        if ($type == 'POST') {
            $toWeek = 17;
        }

        $games = NflGame::where('week', '<=', $toWeek)
            ->get();

        $teams = NflTeam::all()->pluck('abbr')->toArray();
        $zeros = array_fill(0, count($teams), ['wins' => 0, 'losses' => 0]);
        $teamsWinLoss = array_combine($teams, $zeros);

        // set each teams wins and losses
        foreach($games as $game) {
            $teamsWinLoss[$game->winning_team_id]['wins']++;
            $teamsWinLoss[$game->losing_team_id]['losses']++;
        }

        // apply win/loss to teams
        foreach(Team::all() as $team) {
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
        }
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
}
