<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCommit extends Model
{
    protected $fillable = ['user_id', 'text', 'date', 'locked_at', 'unlocked_count'];

    protected $casts = [
        'date' => 'date',
        'locked_at' => 'datetime',
    ];

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function canUnlock(): bool
    {
        return $this->unlocked_count < 1;
    }
}
