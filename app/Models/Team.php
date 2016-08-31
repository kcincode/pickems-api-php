<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'name', 'paid', 'points', 'playoffs', 'wl',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
