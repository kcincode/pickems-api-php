<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class Storyline extends Model
{
    /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'user_id', 'week', 'title', 'story',
  ];

    protected $dates = [
      'posted_at',
  ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
