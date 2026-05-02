<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class HistoryController extends Controller
{
    public function index()
    {
        $history = Task::where('user_id', Auth::id())
            ->where('done', true)
            ->whereNotNull('done_at')
            ->orderByDesc('done_at')
            ->get()
            ->groupBy(fn($task) => \Carbon\Carbon::parse($task->done_at)->toDateString());

        return view('history.index', compact('history'));
    }
}
