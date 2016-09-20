<?php

namespace Pickems\Console\Commands;

use Pickems\NflScrape;
use Pickems\Models\NflGame;
use Pickems\Models\NflStat;
use Illuminate\Console\Command;

class PickemsFetch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickems:fetch {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches data from the specified year up to the current date';

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
        list($type, $week) = explode('-', NflGame::currentWeek());

        // don't fetch anything
        if ($week == 1 && $type == 'REG') {
            $this->info('No stats to fetch yet');
            return;
        }

        $this->nflScrape = new NflScrape($this->argument('year'));

        // fetch stats for regular season
        $toWeek = ($type == 'REG') ? $week - 1 : 17;
        $start = microtime(true);
        $this->info("Fetching regular season stats to week {$toWeek}:");
        $bar = $this->output->createProgressBar($toWeek);
        foreach(range(1, $toWeek) as $w) {
            $this->fetchWeekStats($w);
            $bar->advance();
        }
        $bar->finish();
        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time. ' seconds');



        // fetch stats for post season
        if ($type == 'POST') {
            $start = microtime(true);
            $toWeek = $week - 1;
            $this->info("Fetching post season stats to week {$toWeek}:");
            $bar = $this->output->createProgressBar($toWeek);
            foreach(range(1, $toWeek) as $w) {
                $this->fetchWeekStats($w);
                $bar->advance();
            }
            $bar->finish();
            $time = number_format(microtime(true) - $start, 2);
            $this->info('    '.$time. ' seconds');

        }
    }

    private function fetchWeekStats($week)
    {
        $stats = $this->nflScrape->fetchStats($week);

        foreach ($stats as $stat) {
            // if there is a winner
            if (array_key_exists('winning_team_id', $stat)) {
                // find the game
                $game = NflGame::where('eid', '=', $stat['eid'])->first();
                
                // update team winner and loser
                $game->winning_team_id = $stat['winning_team_id'];
                $game->losing_team_id = $stat['losing_team_id'];
                $game->save();
            }

            // create/update the game stats
            NflStat::updateOrCreate($week, 'team', $stat['home_team_id'], $stat['homediff']);
            NflStat::updateOrCreate($week, 'team', $stat['away_team_id'], $stat['awaydiff']);

            // create/update all the player stats
            foreach ($stat['stats'] as $playerGsisId => $playerStat) {
                // var_dump($playerGsisId, $playerStat);
                NflStat::updateOrCreate($week, 'player', $playerGsisId, $playerStat);
            }
        }
    }
}
