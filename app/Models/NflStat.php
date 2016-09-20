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
        $this->belongsTo(NflPlayer::class, 'sdfasfd');
    }

    public function team()
    {
        $this->belongsTo(NflTeam::class ,'asdfdsa');
    }

    public static function updateOrCreate($week, $type, $id, $data = null)
    {
        if ($type == 'team') {
            $stat = self::where('team_id', '=', $id)
                ->where('week', '=', $week)
                ->first();

            if (!$stat) {
                // create a new stat
                $stat = self::create(['team_id' => $id, 'week' => $week]);
            }

            // update the stat
            $stat->diff = (is_numeric($data)) ? $data : 0;
            $stat->td = 0;
            $stat->fg = 0;
            $stat->two = 0;
            $stat->xp = 0;

            $stat->save();
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

            if (!$stat) {
                $data['week'] = $week;
                $data['player_id'] = $player->id;
                // create a new stat
                $stat = self::create($data);
            } else if($data) {
                // update the stat
                $stat->td = $data['td'];
                $stat->fg = $data['fg'];
                $stat->two = $data['two'];
                $stat->xp = $data['xp'];
            }

            $stat->diff = 0;
            $stat->save();
        }

        return $stat;
    }
}
