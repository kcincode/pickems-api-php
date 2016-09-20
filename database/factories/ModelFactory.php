<?php
$abbrs = ['ARI','ATL','BAL','BUF','CAR','CHI','CIN','CLE','DAL','DEN','DET','GB','HOU','IND','JAX','KC','MIA','MIN','NE','NO','NYG','NYJ','OAK','PHI','PIT','SD','SEA','SF','LA','TB','TEN','WAS'];

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

$factory->define(Pickems\Models\User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'role' => 'user',
    ];
});

$factory->define(Pickems\Models\Team::class, function (Faker\Generator $faker) {
    return [
        'user_id' => function() {
            return factory(Pickems\Models\User::class)->create()->id;
        },
        'name' => $faker->company,
        'paid' => false,
        'points' => $faker->numberBetween($min = 100, $max = 700),
        'playoffs' => $faker->numberBetween($min = 50, $max = 300),
        'wl' => $faker->randomFloat($nbMaxDecimals = 3, $min = 0, $max = 1),
    ];
});

$factory->define(Pickems\Models\NflGame::class, function (Faker\Generator $faker) {
    $eid = $faker->numberBetween($min = 2016000000, $max = 2016123100);

    return [
        'starts_at' => $faker->dateTime,
        'week' => $faker->numberBetween($min = 1, $max = 17),
        'type' => 'REG',
        'eid' => $eid,
        'gsis' => md5($eid),
        'home_team_id' => function() {
            return factory(Pickems\Models\NflTeam::class)->create()->abbr;
        },
        'away_team_id' => function() {
            return factory(Pickems\Models\NflTeam::class)->create()->abbr;
        },
        'winning_team_id' => null,
        'losing_team_id' => null,
    ];
});

$factory->define(Pickems\Models\NflTeam::class, function (Faker\Generator $faker) use ($abbrs) {
    return [
        'abbr' => $faker->unique()->randomElement($abbrs),
        'conference' => $faker->randomElement(['NFC', 'AFC']),
        'city' => $faker->city,
        'name' => $faker->company,
    ];
});

$factory->define(Pickems\Models\NflPlayer::class, function (Faker\Generator $faker) {
    $profile = $faker->numberBetween($min = 00000, $max = 99999);
    return [
        'team_id' => function() {
            return factory(Pickems\Models\NflTeam::class)->create()->abbr;
        },
        'gsis_id' => md5($profile),
        'profile_id' => '00-'.$profile,
        'name' => $faker->name,
        'position' => $faker->randomElement(['QB', 'RB', 'WRTE', 'K']),
        'active' => true,
    ];
});


$factory->define(Pickems\Models\TeamPick::class, function (Faker\Generator $faker) {
    $isValid = $faker->boolean;
    $week = $faker->numberBetween($min = 1, $max = 17);

    return [
        'team_id' => function() {
            return factory(Pickems\Models\Team::class)->create()->id;
        },
        'week' => $week,
        'number' => 1,
        'nfl_stat_id' => function() use ($week) {
            return factory(Pickems\Models\NflStat::class)->create(['week' => $week])->id;
        },
        'playmaker' => $faker->boolean,
        'valid' => $isValid,
        'reason' => ($isValid) ? null : 'This is the reason',
        'picked_at' => $faker->datetime,
    ];
});

$factory->define(Pickems\Models\NflStat::class, function (Faker\Generator $faker) {
    $type = ($faker->boolean) ? 'player' : 'team';

    return [
        'week' => $faker->numberBetween($min = 1, $max = 17),
        'player_id' => function() use ($type) {
            return ($type == 'player') ? factory(Pickems\Models\NflPlayer::class)->create()->gsis_id : null;
        },
        'team_id' => function() use ($type) {
            return ($type == 'team') ? factory(Pickems\Models\NflTeam::class)->create()->abbr : null;
        },
        'td' => ($type == 'player') ? $faker->numberBetween($min = 0, $max = 3) : 0,
        'fg' => ($type == 'player') ? $faker->numberBetween($min = 0, $max = 3) : 0,
        'two' => ($type == 'player') ? $faker->numberBetween($min = 0, $max = 1) : 0,
        'xp' => ($type == 'player') ? $faker->numberBetween($min = 0, $max = 3) : 0,
        'diff' => ($type == 'team') ? $faker->numberBetween($min = -25, $max = 25) : 0,
    ];
});
