<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class HistoryController extends Controller
{
    public function index()
    {
        $user      = Auth::user();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();

        // Only tasks from past completed weeks (exclude current week)
        $pastTasks = Task::where('user_id', $user->id)
            ->where('date', '<', $weekStart)
            ->get();

        $sections = ['must', 'should', 'good', 'park'];
        $sectionMeta = [
            'must'   => ['label' => 'Must',    'color' => '#FF4F00'],
            'should' => ['label' => 'Should',  'color' => '#FFC900'],
            'good'   => ['label' => 'Good',    'color' => '#23A094'],
            'park'   => ['label' => 'Parking', 'color' => '#B0B0A8'],
        ];

        $weeks = $pastTasks
            ->groupBy(fn($t) => Carbon::parse($t->date)->startOfWeek(Carbon::MONDAY)->toDateString())
            ->map(function ($weekTasks, $weekStartDate) use ($sections, $sectionMeta) {
                $start = Carbon::parse($weekStartDate);
                $end   = $start->copy()->endOfWeek(Carbon::SUNDAY);

                $sectionStats = collect($sections)->mapWithKeys(function ($section) use ($weekTasks, $sectionMeta) {
                    $total = $weekTasks->where('section', $section)->count();
                    $done  = $weekTasks->where('section', $section)->where('done', true)->count();
                    return [$section => [
                        'label' => $sectionMeta[$section]['label'],
                        'color' => $sectionMeta[$section]['color'],
                        'total' => $total,
                        'done'  => $done,
                        'rate'  => $total > 0 ? round(($done / $total) * 100) : null,
                    ]];
                });

                $totalTasks = $weekTasks->count();
                $totalWins  = $weekTasks->where('done', true)->count();

                return [
                    'label'        => $start->format('d M') . ' — ' . $end->format('d M Y'),
                    'week_start'   => $start,
                    'total_wins'   => $totalWins,
                    'total_tasks'  => $totalTasks,
                    'week_rate'    => $totalTasks > 0 ? round(($totalWins / $totalTasks) * 100) : 0,
                    'section_stats'=> $sectionStats,
                ];
            })
            ->sortByDesc('week_start')
            ->values();

        return view('history.index', compact('weeks'));
    }
}
