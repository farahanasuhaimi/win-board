<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\UserStat;
use App\Services\GamificationService;
use App\Services\QuestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $rules = [
            'text'    => 'required|string|max:500',
            'section' => 'required|in:must,should,good,park',
            'estimated_minutes' => 'nullable|integer|in:10,30,60,120,360',
        ];
        if ($request->section === 'must') {
            $rules['estimated_minutes'] = 'required|integer|in:10,30,60,120,360';
        }
        $request->validate($rules);

        $user = Auth::user();
        $today = now()->toDateString();

        if ($request->section === 'must') {
            $mustCount = Task::where('user_id', $user->id)
                ->where('section', 'must')
                ->where('done', false)
                ->count();

            if ($mustCount >= 3) {
                return response()->json(['error' => 'Maximum 3 must-do tasks. Prioritise.'], 422);
            }
        }

        $task = Task::create([
            'user_id'            => $user->id,
            'text'               => $request->text,
            'section'            => $request->section,
            'date'               => $today,
            'sort_order'         => Task::where('user_id', $user->id)->where('date', $today)->where('section', $request->section)->count(),
            'estimated_minutes'  => $request->estimated_minutes,
        ]);

        return response()->json($task);
    }

    public function toggle(Task $task)
    {
        $this->authorise($task);

        $task->done   = !$task->done;
        $task->done_at = $task->done ? now() : null;
        $task->save();

        if (!$task->done) {
            return response()->json(['done' => false]);
        }

        $stat = UserStat::firstOrCreate(['user_id' => $task->user_id]);
        $stat->increment('total_wins');
        $stat->refresh();

        // Read comeback state before updateStreak can decrement it
        $comebackActive = $stat->comeback_days_left > 0;
        $multiplier     = $comebackActive ? 2 : 1;

        $shieldGranted = $this->updateStreak($stat);
        $stat->refresh();

        // Calculate total XP for this task + any completed quests
        $today   = now()->toDateString();
        $baseXp  = GamificationService::xpForSection($task->section);

        $questResult = QuestService::evaluate($task->user_id, $today);
        $questXp     = collect($questResult['completed'])->sum('xp');
        if ($questResult['all_bonus']) $questXp += 20;

        $totalXp  = ($baseXp + $questXp) * $multiplier;
        $xpResult = GamificationService::grantXp($stat, $totalXp);

        return response()->json([
            'done'            => true,
            'xp_gained'       => $totalXp,
            'total_xp'        => $xpResult['total_xp'],
            'level_up'        => $xpResult['level_up'],
            'level'           => $xpResult['level'],
            'level_title'     => $xpResult['title'],
            'progress_pct'    => $xpResult['progress_pct'],
            'xp_in_level'     => $xpResult['xp_in_level'],
            'xp_for_level'    => $xpResult['xp_for_level'],
            'is_comeback'     => $comebackActive,
            'quest_completed' => $questResult['completed'],
            'all_quests_bonus' => $questResult['all_bonus'],
            'shield_granted'  => $shieldGranted,
        ]);
    }

    public function move(Request $request, Task $task)
    {
        $this->authorise($task);

        $allowed = [
            'must'   => ['park'],
            'should' => ['good', 'park'],
            'good'   => ['should', 'park'],
            'park'   => ['good', 'should'],
        ];

        $to = $request->input('section');

        if (!isset($allowed[$task->section]) || !in_array($to, $allowed[$task->section])) {
            return response()->json(['error' => 'Move not allowed.'], 422);
        }

        if ($to === 'should') {
            $count = Task::where('user_id', $task->user_id)
                ->where('date', $task->date)
                ->where('section', 'should')
                ->where('done', false)
                ->count();
            if ($count >= 5) {
                return response()->json(['error' => 'Should Do is full (max 5).'], 422);
            }
        }

        $task->section = $to;
        $task->save();

        return response()->json(['section' => $to]);
    }

    public function promote(Task $task)
    {
        $this->authorise($task);

        $tomorrow = now()->addDay()->toDateString();

        Task::create([
            'user_id' => $task->user_id,
            'text' => $task->text,
            'section' => 'should',
            'date' => $tomorrow,
            'sort_order' => 0,
        ]);

        $task->delete();

        return response()->json(['ok' => true]);
    }

    public function destroy(Task $task)
    {
        $this->authorise($task);
        $task->delete();
        return response()->json(['ok' => true]);
    }

    private function authorise(Task $task): void
    {
        abort_unless($task->user_id === Auth::id(), 403);
    }

    private function updateStreak(UserStat $stat): bool
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $lastDate  = $stat->last_active_date?->toDateString();
        $isNewDay  = $lastDate !== $today;
        $shieldGranted = false;
        $streakBroken  = false;

        if ($lastDate === $yesterday) {
            $stat->streak += 1;
            if ($stat->streak % 7 === 0 && $stat->shields < 3) {
                $stat->shields = min(3, $stat->shields + 1);
                $shieldGranted = true;
            }
        } elseif ($isNewDay) {
            $gapDays = $lastDate
                ? (int) \Carbon\Carbon::parse($lastDate)->diffInDays($today) - 1
                : 999;

            if ($gapDays === 1 && $stat->shields > 0) {
                // One missed day — auto-consume a shield
                $stat->shields -= 1;
                $stat->streak  += 1;
            } else {
                $stat->streak            = 1;
                $stat->comeback_days_left = 3;
                $streakBroken = true;
            }
        }

        // Decrement comeback on first task of each new day (but not the day it was just set)
        if ($isNewDay && !$streakBroken && $stat->comeback_days_left > 0) {
            $stat->comeback_days_left -= 1;
        }

        $stat->last_active_date = $today;
        $stat->save();

        return $shieldGranted;
    }
}
