<?php

namespace App\Http\Controllers;

use App\Models\DailyCommit;
use App\Models\Task;
use App\Models\UserStat;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $commit = DailyCommit::where('user_id', $user->id)->where('date', $today)->first();

        // Undone Should tasks from previous days escalate to Must
        Task::where('user_id', $user->id)
            ->where('date', '<', $today)
            ->where('done', false)
            ->where('section', 'should')
            ->update(['section' => 'must']);

        // Parking lot tasks roll forward to today
        Task::where('user_id', $user->id)
            ->where('date', '<', $today)
            ->where('done', false)
            ->where('section', 'park')
            ->update(['date' => $today]);

        $todayTasks = Task::where('user_id', $user->id)
            ->where('date', $today)
            ->orderBy('done')
            ->orderBy('sort_order')
            ->get()
            ->each(fn($t) => $t->days_late = 0);

        $carryForward = Task::where('user_id', $user->id)
            ->where('date', '<', $today)
            ->where(function ($q) use ($today) {
                $q->where('done', false)
                  ->orWhere('done_at', '>=', $today);
            })
            ->whereIn('section', ['must', 'should', 'good', 'park'])
            ->orderBy('date')
            ->get()
            ->each(function ($task) use ($today) {
                $task->days_late = $task->section === 'park'
                    ? 0
                    : \Carbon\Carbon::parse($task->date)->diffInDays($today);
            });

        // Carry-forward tasks appear first in each section
        $tasks = $carryForward->concat($todayTasks)->groupBy('section');

        $stat = UserStat::firstOrCreate(['user_id' => $user->id]);

        $winsToday = Task::where('user_id', $user->id)
            ->where('date', $today)
            ->where('done', true)
            ->count();

        $commitTaskId = $commit?->task_id;
        $commitDone   = $commitTaskId ? (bool) Task::find($commitTaskId)?->done : false;

        $showOnboarding = !$user->onboarded_at;

        return view('dashboard.index', compact(
            'commit', 'tasks', 'stat', 'winsToday', 'today',
            'commitTaskId', 'commitDone', 'showOnboarding'
        ));
    }

    public function completeOnboarding()
    {
        Auth::user()->update(['onboarded_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function calendarEvents()
    {
        $user = Auth::user();
        $events = (new GoogleCalendarService())->getTodayEvents($user);
        $meetingMinutes = collect($events)->where('all_day', false)->sum('duration');
        return response()->json(['events' => $events, 'meeting_minutes' => $meetingMinutes]);
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
