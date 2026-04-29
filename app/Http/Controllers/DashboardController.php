<?php

namespace App\Http\Controllers;

use App\Models\DailyCommit;
use App\Models\Task;
use App\Models\UserStat;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $commit = DailyCommit::where('user_id', $user->id)->where('date', $today)->first();

        $tasks = Task::where('user_id', $user->id)
            ->where('date', $today)
            ->orderBy('done')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('section');

        $stat = UserStat::firstOrCreate(['user_id' => $user->id]);

        $winsToday = Task::where('user_id', $user->id)
            ->where('date', $today)
            ->where('done', true)
            ->count();

        return view('dashboard.index', compact('commit', 'tasks', 'stat', 'winsToday', 'today'));
    }

    public function reset()
    {
        $user = Auth::user();
        $today = now()->toDateString();

        Task::where('user_id', $user->id)->where('date', $today)->delete();
        DailyCommit::where('user_id', $user->id)->where('date', $today)->delete();

        return response()->json(['ok' => true]);
    }
}
