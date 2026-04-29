<?php

namespace App\Http\Controllers;

use App\Models\DailyCommit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommitController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['text' => 'required|string|max:500']);

        $user = Auth::user();
        $today = now()->toDateString();

        $existing = DailyCommit::where('user_id', $user->id)->where('date', $today)->first();

        if ($existing && $existing->isLocked() && !$existing->canUnlock()) {
            return response()->json(['error' => 'Commitment already locked for today.'], 422);
        }

        if ($existing && $existing->isLocked()) {
            $existing->update([
                'text' => $request->text,
                'locked_at' => now(),
                'unlocked_count' => $existing->unlocked_count + 1,
            ]);
            return response()->json($existing);
        }

        $commit = DailyCommit::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['text' => $request->text, 'locked_at' => now()]
        );

        return response()->json($commit);
    }

    public function unlock(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $commit = DailyCommit::where('user_id', $user->id)->where('date', $today)->firstOrFail();

        if (!$commit->canUnlock()) {
            return response()->json(['error' => 'Already used your one unlock for today.'], 422);
        }

        $commit->update(['locked_at' => null]);

        return response()->json(['ok' => true]);
    }
}
