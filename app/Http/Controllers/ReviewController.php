<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReviewController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd   = now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        // Tasks assigned this week — used for section completion stats
        $weekTasks = Task::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get();

        // Tasks completed this week (by done_at) — used for wins per day chart
        $completedThisWeek = Task::where('user_id', $user->id)
            ->where('done', true)
            ->whereNotNull('done_at')
            ->whereBetween('done_at', [$weekStart, $weekEnd])
            ->get();

        // Wins per day Mon–Sun
        $days = collect();
        for ($i = 0; $i < 7; $i++) {
            $date     = $weekStart->copy()->addDays($i);
            $dateStr  = $date->toDateString();
            $isFuture = $dateStr > $today;

            $days->push([
                'date'      => $dateStr,
                'label'     => $date->format('D'),
                'wins'      => $completedThisWeek->filter(
                    fn($t) => Carbon::parse($t->done_at)->toDateString() === $dateStr
                )->count(),
                'total'     => $weekTasks->where('date', $dateStr)->count(),
                'is_today'  => $dateStr === $today,
                'is_future' => $isFuture,
            ]);
        }

        // Completion rate by section (based on tasks assigned this week)
        $sections    = ['must', 'should', 'good', 'park'];
        $sectionStats = collect($sections)->mapWithKeys(function ($section) use ($weekTasks) {
            $total = $weekTasks->where('section', $section)->count();
            $done  = $weekTasks->where('section', $section)->where('done', true)->count();
            return [$section => [
                'total' => $total,
                'done'  => $done,
                'rate'  => $total > 0 ? round(($done / $total) * 100) : 0,
            ]];
        });

        $totalWins  = $completedThisWeek->count();
        $totalTasks = $weekTasks->count();
        $weekRate   = $totalTasks > 0 ? round(($totalWins / $totalTasks) * 100) : 0;

        $weekLabel = $weekStart->format('d M') . ' — ' . $weekEnd->format('d M Y');

        $stat = \App\Models\UserStat::firstOrCreate(['user_id' => $user->id]);

        return view('review.index', compact(
            'days', 'sectionStats', 'totalWins', 'totalTasks', 'weekRate', 'stat', 'weekLabel'
        ));
    }
}
