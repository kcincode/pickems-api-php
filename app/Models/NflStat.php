<?php

namespace Pickems\Models;

use Illuminate\Database\Eloquent\Model;

class NflStat extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'week', 'player_id', 'team_id', 'td', 'fg', 'two', 'xp', 'diff',
    ];

    public function player()
    {
        $this->belongsTo(NflPlayer::class, 'player_id');
    }

    public function team()
    {
        $this->belongsTo(NflTeam::class, 'team_id');
    }

    public static function updateOrCreate($week, $type, $id, $data)
    {
        if ($type == 'team') {
            $stat = self::where('team_id', '=', $id)
                ->where('week', '=', $week)
                ->first();

            if ($stat) {
                // update the stat
                $stat->td = 0;
                $stat->fg = 0;
                $stat->two = 0;
                $stat->xp = 0;
                $stat->diff = $data;
                $stat->save();
            } else {
                // create a new stat
                $stat = self::create(['team_id' => $id, 'diff' => $data, 'week' => $week]);
            }
        } else if ($type == 'player') {
            $player = NflPlayer::where('gsis_id', '=', $id)
                ->where('active', '=', true)
                ->first();

            if (!$player) {
                return null;
            }

            $stat = self::where('player_id', '=', $player->id)
                ->where('week', '=', $week)
                ->first();

            if ($stat) {
                // update the stat
                $stat->td = $data['td'];
                $stat->fg = $data['fg'];
                $stat->two = $data['two'];
                $stat->xp = $data['xp'];
                $stat->diff = 0;
                $stat->save();
            } else {
                $data['week'] = $week;
                $data['player_id'] = $player->id;
                // create a new stat
                $stat = self::create($data);
            }
        }

        return $stat;
    }
}
