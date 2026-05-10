<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyQuest extends Model
{
    protected $fillable = ['user_id', 'date', 'quest_type', 'completed', 'completed_at', 'xp_reward'];

    protected $casts = [
        'date'         => 'date',
        'completed'    => 'boolean',
        'completed_at' => 'datetime',
    ];
}
