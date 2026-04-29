<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'text', 'section', 'done', 'date', 'sort_order', 'done_at'];

    protected $casts = [
        'done' => 'boolean',
        'date' => 'date',
        'done_at' => 'datetime',
    ];

    public function scopeForToday($query, int $userId, string $date)
    {
        return $query->where('user_id', $userId)->where('date', $date);
    }
}
