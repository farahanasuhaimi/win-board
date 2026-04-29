<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    protected $fillable = ['user_id', 'streak', 'total_wins', 'last_active_date'];

    protected $casts = [
        'last_active_date' => 'date',
    ];
}
