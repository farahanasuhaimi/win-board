<?php

namespace App\Services;

use App\Models\UserStat;

class GamificationService
{
    const XP_BY_SECTION = [
        'must'   => 30,
        'should' => 20,
        'good'   => 10,
        'park'   => 0,
    ];

    const LEVELS = [
        ['xp' => 0,     'title' => 'Starter'],
        ['xp' => 150,   'title' => 'Consistent'],
        ['xp' => 400,   'title' => 'Momentum Builder'],
        ['xp' => 800,   'title' => 'Focused'],
        ['xp' => 1500,  'title' => 'Win Machine'],
        ['xp' => 2500,  'title' => 'Relentless'],
        ['xp' => 4000,  'title' => 'Elite'],
        ['xp' => 6000,  'title' => 'Unstoppable'],
        ['xp' => 9000,  'title' => 'Legend'],
        ['xp' => 13000, 'title' => 'GOAT'],
    ];

    public static function xpForSection(string $section): int
    {
        return self::XP_BY_SECTION[$section] ?? 0;
    }

    public static function getLevelInfo(int $xp): array
    {
        $level     = 1;
        $levelData = self::LEVELS[0];
        $nextData  = self::LEVELS[1] ?? null;

        foreach (self::LEVELS as $i => $data) {
            if ($xp >= $data['xp']) {
                $level     = $i + 1;
                $levelData = $data;
                $nextData  = self::LEVELS[$i + 1] ?? null;
            }
        }

        $xpInLevel   = $xp - $levelData['xp'];
        $xpForLevel  = $nextData ? ($nextData['xp'] - $levelData['xp']) : null;
        $progressPct = $xpForLevel ? min(100, (int) round($xpInLevel / $xpForLevel * 100)) : 100;

        return [
            'level'        => $level,
            'title'        => $levelData['title'],
            'xp_in_level'  => $xpInLevel,
            'xp_for_level' => $xpForLevel,
            'progress_pct' => $progressPct,
            'is_max'       => $nextData === null,
        ];
    }

    /**
     * Grant XP to a user stat. Amount should already include the comeback multiplier.
     * Returns the resulting level state.
     */
    public static function grantXp(UserStat $stat, int $amount): array
    {
        if ($amount <= 0) {
            return array_merge(self::getLevelInfo($stat->xp), ['level_up' => false, 'total_xp' => $stat->xp]);
        }

        $before = self::getLevelInfo($stat->xp);
        $stat->xp += $amount;
        $after = self::getLevelInfo($stat->xp);
        $stat->level = $after['level'];
        $stat->save();

        return array_merge($after, [
            'level_up'  => $after['level'] > $before['level'],
            'total_xp'  => $stat->xp,
        ]);
    }
}
