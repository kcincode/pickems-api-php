<?php

namespace Pickems\Console\Commands;

use Pickems\Models\NflGame;
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
            // $this->bestPicks($type, $week - 1);
            $this->teamScoresAndRankings($type, $week - 1);
        }
    }

    private function weeklyLeaders($type, $toWeek)
    {
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
}
