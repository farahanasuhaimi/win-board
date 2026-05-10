<?php

namespace App\Services;

use App\Models\DailyQuest;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QuestService
{
    const POOL = [
        'complete_must' => [
            'label'       => 'Clear all Musts',
            'description' => 'Complete 3 Must tasks today',
            'target'      => 3,
        ],
        'complete_should' => [
            'label'       => 'Should Champion',
            'description' => 'Complete 2 Should tasks today',
            'target'      => 2,
        ],
        'complete_good' => [
            'label'       => 'Nice Touch',
            'description' => 'Complete a Good task today',
            'target'      => 1,
        ],
        'early_finisher' => [
            'label'       => 'Early Bird',
            'description' => 'Complete a Must task before noon',
            'target'      => 1,
        ],
        'five_wins' => [
            'label'       => 'High Five',
            'description' => 'Complete 5 tasks total today',
            'target'      => 5,
        ],
    ];

    public static function getOrGenerate(int $userId, string $date): Collection
    {
        $quests = DailyQuest::where('user_id', $userId)->where('date', $date)->get();

        if ($quests->count() !== 3) {
            DailyQuest::where('user_id', $userId)->where('date', $date)->delete();
            $picks = collect(array_keys(self::POOL))->shuffle()->take(3);
            foreach ($picks as $type) {
                DailyQuest::create([
                    'user_id'    => $userId,
                    'date'       => $date,
                    'quest_type' => $type,
                    'xp_reward'  => 10,
                ]);
            }
            $quests = DailyQuest::where('user_id', $userId)->where('date', $date)->get();
        }

        return $quests;
    }

    public static function getProgress(int $userId, string $date, string $questType): int
    {
        $start = Carbon::parse($date)->startOfDay();
        $end   = Carbon::parse($date)->endOfDay();
        $noon  = Carbon::parse($date)->setHour(12)->startOfMinute();

        return match ($questType) {
            'complete_must' => Task::where('user_id', $userId)
                ->where('section', 'must')->where('done', true)
                ->whereBetween('done_at', [$start, $end])->count(),

            'complete_should' => Task::where('user_id', $userId)
                ->where('section', 'should')->where('done', true)
                ->whereBetween('done_at', [$start, $end])->count(),

            'complete_good' => Task::where('user_id', $userId)
                ->where('section', 'good')->where('done', true)
                ->whereBetween('done_at', [$start, $end])->count(),

            'early_finisher' => Task::where('user_id', $userId)
                ->where('section', 'must')->where('done', true)
                ->whereBetween('done_at', [$start, $noon])->count(),

            'five_wins' => Task::where('user_id', $userId)
                ->where('done', true)
                ->whereBetween('done_at', [$start, $end])->count(),

            default => 0,
        };
    }

    /**
     * Check all incomplete quests for today. Mark completed ones, return results.
     * Returns: ['completed' => [...], 'all_bonus' => bool]
     */
    public static function evaluate(int $userId, string $date): array
    {
        $incomplete = DailyQuest::where('user_id', $userId)
            ->where('date', $date)
            ->where('completed', false)
            ->get();

        $completed = [];
        foreach ($incomplete as $quest) {
            $def = self::POOL[$quest->quest_type] ?? null;
            if (!$def) continue;

            $progress = self::getProgress($userId, $date, $quest->quest_type);
            if ($progress >= $def['target']) {
                $quest->completed    = true;
                $quest->completed_at = now();
                $quest->save();
                $completed[] = ['type' => $quest->quest_type, 'label' => $def['label'], 'xp' => $quest->xp_reward];
            }
        }

        // All-quests bonus fires only when this batch completes the last quest
        $allDone = DailyQuest::where('user_id', $userId)->where('date', $date)->where('completed', true)->count();
        $allBonus = count($completed) > 0 && $allDone === 3;

        return ['completed' => $completed, 'all_bonus' => $allBonus];
    }

    /**
     * Attach live progress to a collection of DailyQuest models for display.
     */
    public static function withProgress(Collection $quests, int $userId, string $date): array
    {
        return $quests->map(function (DailyQuest $quest) use ($userId, $date) {
            $def      = self::POOL[$quest->quest_type] ?? [];
            $target   = $def['target'] ?? 1;
            $progress = $quest->completed
                ? $target
                : min($target, self::getProgress($userId, $date, $quest->quest_type));

            return [
                'type'        => $quest->quest_type,
                'label'       => $def['label'] ?? $quest->quest_type,
                'description' => $def['description'] ?? '',
                'target'      => $target,
                'progress'    => $progress,
                'completed'   => $quest->completed,
                'xp'          => $quest->xp_reward,
            ];
        })->values()->all();
    }
}
