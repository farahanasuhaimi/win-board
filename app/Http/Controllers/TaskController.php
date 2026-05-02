<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\UserStat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'text' => 'required|string|max:500',
            'section' => 'required|in:must,should,good,park',
        ]);

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
            'user_id' => $user->id,
            'text' => $request->text,
            'section' => $request->section,
            'date' => $today,
            'sort_order' => Task::where('user_id', $user->id)->where('date', $today)->where('section', $request->section)->count(),
        ]);

        return response()->json($task);
    }

    public function toggle(Task $task)
    {
        $this->authorise($task);

        $task->done = !$task->done;
        $task->done_at = $task->done ? now() : null;
        $task->save();

        if ($task->done) {
            $stat = UserStat::firstOrCreate(['user_id' => $task->user_id]);
            $stat->increment('total_wins');
            $this->updateStreak($stat);
        }

        return response()->json(['done' => $task->done]);
    }

    public function move(Request $request, Task $task)
    {
        $this->authorise($task);

        $allowed = [
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

    private function updateStreak(UserStat $stat): void
    {
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if ($stat->last_active_date?->toDateString() === $yesterday) {
            $stat->streak += 1;
        } elseif ($stat->last_active_date?->toDateString() !== $today) {
            $stat->streak = 1;
        }

        $stat->last_active_date = $today;
        $stat->save();
    }
}
