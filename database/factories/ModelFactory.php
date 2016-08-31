<?php

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
        'paid' => $faker->boolean,
        'points' => $faker->numberBetween($min = 100, $max = 700),
        'playoffs' => $faker->numberBetween($min = 50, $max = 300),
        'wl' => $faker->randomFloat($nbMaxDecimals = 3, $min = 0, $max = 1),
    ];
});
