<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    protected $fillable = [
        'user_id', 'streak', 'total_wins', 'last_active_date',
        'xp', 'level', 'shields', 'comeback_days_left',
    ];

    protected $casts = [
        'last_active_date' => 'date',
    ];
}
