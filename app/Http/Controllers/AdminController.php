<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\UserStat;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $users = User::with('stat')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($user) use ($today) {
                $user->wins_today   = Task::where('user_id', $user->id)->where('date', $today)->where('done', true)->count();
                $user->tasks_today  = Task::where('user_id', $user->id)->where('date', $today)->count();
                $user->total_wins   = $user->stat?->total_wins ?? 0;
                $user->streak       = $user->stat?->streak ?? 0;
                return $user;
            });

        $dailyActiveUsers = Task::where('date', $today)
            ->distinct('user_id')
            ->count('user_id');

        $tasksCreatedToday = Task::whereDate('created_at', $today)->count();
        $winsToday         = Task::where('date', $today)->where('done', true)->count();
        $totalUsers        = User::count();

        return view('admin.index', compact(
            'users', 'dailyActiveUsers', 'tasksCreatedToday', 'winsToday', 'totalUsers'
        ));
    }

    public function toggleAdmin(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot change your own admin status.');
        $user->is_admin = !$user->is_admin;
        $user->save();
        return back();
    }
}
