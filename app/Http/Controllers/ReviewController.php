<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ReviewController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = now()->toDateString();
        $weekStart = now()->subDays(6)->toDateString();

        $weekTasks = Task::where('user_id', $user->id)
            ->whereBetween('date', [$weekStart, $today])
            ->get();

        // Wins per day for last 7 days
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $days->push([
                'date'  => $date,
                'label' => Carbon::parse($date)->format('D'),
                'wins'  => $weekTasks->where('date', $date)->where('done', true)->count(),
                'total' => $weekTasks->where('date', $date)->count(),
            ]);
        }

        // Completion rate by section
        $sections = ['must', 'should', 'good', 'park'];
        $sectionStats = collect($sections)->mapWithKeys(function ($section) use ($weekTasks) {
            $total = $weekTasks->where('section', $section)->count();
            $done  = $weekTasks->where('section', $section)->where('done', true)->count();
            return [$section => [
                'total' => $total,
                'done'  => $done,
                'rate'  => $total > 0 ? round(($done / $total) * 100) : 0,
            ]];
        });

        $totalWins  = $weekTasks->where('done', true)->count();
        $totalTasks = $weekTasks->count();
        $weekRate   = $totalTasks > 0 ? round(($totalWins / $totalTasks) * 100) : 0;

        $stat = \App\Models\UserStat::firstOrCreate(['user_id' => $user->id]);

        return view('review.index', compact('days', 'sectionStats', 'totalWins', 'totalTasks', 'weekRate', 'stat'));
    }
}
