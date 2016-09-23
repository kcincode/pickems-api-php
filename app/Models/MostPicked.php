<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class MostPicked extends Model
{
    protected $table = 'most_picked';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type', 'week', 'name', 'number_picked',
    ];
}
