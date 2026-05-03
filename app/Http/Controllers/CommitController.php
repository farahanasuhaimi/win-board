<?php

namespace App\Http\Controllers;

use App\Models\DailyCommit;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'text'    => 'required|string|max:500',
            'task_id' => 'nullable|integer|exists:tasks,id',
        ]);

        $user  = Auth::user();
        $today = now()->toDateString();

        if ($request->task_id) {
            Task::where('id', $request->task_id)
                ->where('user_id', $user->id)
                ->where('section', 'must')
                ->firstOrFail();
        }

        $existing = DailyCommit::where('user_id', $user->id)->where('date', $today)->first();

        if ($existing && $existing->isLocked() && !$existing->canUnlock()) {
            return response()->json(['error' => 'Commitment already locked for today.'], 422);
        }

        $data = [
            'text'    => $request->text,
            'task_id' => $request->task_id,
            'locked_at' => now(),
        ];

        if ($existing && $existing->isLocked()) {
            $existing->update(array_merge($data, ['unlocked_count' => $existing->unlocked_count + 1]));
            return response()->json($existing);
        }

        $commit = DailyCommit::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            $data
        );

        return response()->json($commit);
    }

    public function unlock(Request $request)
    {
        $user  = Auth::user();
        $today = now()->toDateString();

        $commit = DailyCommit::where('user_id', $user->id)->where('date', $today)->firstOrFail();

        if (!$commit->canUnlock()) {
            return response()->json(['error' => 'Already used your one unlock for today.'], 422);
        }

        $commit->update(['locked_at' => null]);

        return response()->json(['ok' => true]);
    }
}
