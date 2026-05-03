<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyCommit extends Model
{
    protected $fillable = ['user_id', 'text', 'task_id', 'date', 'locked_at', 'unlocked_count'];

    public function task()
    {
        return $this->belongsTo(\App\Models\Task::class);
    }

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
