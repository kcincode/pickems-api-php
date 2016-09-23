<?php

namespace Pickems\Console\Commands;

use Carbon\Carbon;
use Pickems\Models\User;
use Pickems\Models\Team;
use Pickems\Models\NflGame;
use Pickems\Models\NflStat;
use Pickems\Models\TeamPick;
use Pickems\Models\NflPlayer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PickemsPopulate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pickems:populate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the database with test data';

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
        // clear users except admin
        $user = User::find(1)->toArray();
        $user['password'] = bcrypt('testing');
        DB::table('users')->truncate();
        User::create($user);

        DB::table('team_picks')->truncate();
        DB::table('teams')->truncate();

        // create 25 users
        $users = factory(User::class, 25)->create();
        foreach($users as $user) {
            $team = factory(Team::class)->create(['user_id' => $user->id]);

            $this->makeRegularSeasonPicks($team->id);
        }
    }

    private function makeRegularSeasonPicks($teamId)
    {
        $pickedAt = Carbon::createFromFormat('Y-m-d H:i:s', NflGame::first()->starts_at)->subHour();

        foreach(range(1, 17) as $week) {
            $picksLeft = [
                'QB' => 8,
                'RB' => 8,
                'WRTE' => 8,
                'K' => 8,
                'playmakers' => 2,
                'afc' => 1,
                'nfc' => 1,
                'picked' => [],
            ];

            $this->makePick($teamId, $week, 1, $pickedAt, $picksLeft);
            $this->makePick($teamId, $week, 2, $pickedAt, $picksLeft);
        }
    }

    private function makePick($teamId, $week, $number, $pickedAt, &$picksLeft)
    {
        $pickType = $this->getPickType($picksLeft);
        $nflStat = $this->makeRandomPick($week, $pickType, $picksLeft);

        $playmaker = ($picksLeft['playmakers'] > 0 && mt_rand(1, 100) > 50) ? true : false;
        if ($playmaker) {
            $picksLeft['playmakers']--;
        }

        $teamPick = TeamPick::create([
            'team_id' => $teamId,
            'week' => $week,
            'number' => $number,
            'nfl_stat_id' => $nflStat->id,
            'playmaker' => $playmaker,
            'picked_at' => $pickedAt,
        ]);

        return $teamPick;
    }

    private function getPickType($picksLeft)
    {
        $conferencesLeft = $picksLeft['afc'] > 0 || $picksLeft['nfc'] > 0;
        $positionsLeft =  $picksLeft['QB'] > 0 || $picksLeft['WRTE'] > 0 || $picksLeft['RB'] > 0 || $picksLeft['K'] > 0;

        if ($positionsLeft && $conferencesLeft) {
            return (mt_rand() * 100 > 50) ? 'player' : 'team';
        } else if($positionLeft) {
            return 'player';
        } else if($conferencesLeft)  {
            return 'team';
        }

        throw new Exception('Could not calculate pick type');
    }

    private function makeRandomPick($week, $type, &$picksLeft)
    {
        if ($type == 'player') {
            $player = NflPlayer::join('nfl_teams', 'nfl_teams.abbr', '=', 'nfl_players.team_id')
                ->whereNotIn('nfl_teams.abbr', $picksLeft['picked'])
                ->inRandomOrder()
                ->first();

            $picksLeft[$player->position]--;
            $picksLeft['picked'][] = $player->team->abbr;

            return NflStat::updateOrCreate($week, $type, $player->gsis_id);
        } else {
            $conferences = [];
            if ($picksLeft['afc'] > 0) {
                $conferences[] = 'afc';
            }
            if ($picksLeft['nfc'] > 0) {
                $conferences[] = 'nfc';
            }

            // team pick
            $team = NflTeam::whereIn('conference', $conferences)
                ->inRandomOrder()
                ->first();

            $picksLeft[strtolower($team->conference)]--;

            return NflStat::updateOrCreate($week, $type, $team->abbr);
        }
    }
}