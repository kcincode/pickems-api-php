<?php

namespace Pickems\Console\Commands;

use Hash;
use Pickems\NflScrape;
use Pickems\Models\User;
use Illuminate\Console\Command;

class PickemsInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickems:init {year} {--test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initalizes the database and fetches the initial data.';

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
        // reset the database
        $this->call('migrate:reset');
        $this->call('migrate');

        // fetch the admin information
        $admin = [
            'name' => 'System Admin',
            'role' => 'admin',
        ];
        $admin['email'] = 'admin@felicelli.com'; // $this->ask('Admin email: ');
        $admin['password'] = Hash::make('testing'); // $this->secret('Admin password: '));

        // create the admin
        $adminUser = new User($admin);
        $adminUser->save();

        // setup the nfl scraping
        $this->nflScrape = new NflScrape($this->argument('year'));

        // load the data
        $this->loadNflData('teams', 'Pickems\Models\NflTeam');
        $this->loadNflData('games', 'Pickems\Models\NflGame');
        $this->loadNflData('players', 'Pickems\Models\NflPlayer');

        $this->call('pickems:fetch', ['year' => $this->argument('year')]);
    }

    private function loadNflData($type, $model)
    {
        $start = microtime(true);
        $this->info("Loading NFL {$type}:");
        $items = $this->nflScrape->{'fetch'.ucfirst($type)}();
        $bar = $this->output->createProgressBar(count($items));
        foreach ($items as $item) {
            call_user_func($model.'::fetchOrCreate', $item);
            $bar->advance();
        }
        $bar->finish();

        $time = number_format(microtime(true) - $start, 2);
        $this->info('    '.$time. ' seconds');
    }
}
