@extends('layouts.app')

@section('title', 'Weekly Review — Daily Win Board')

@section('content')
<div class="mb-6">
    <h1 class="font-display font-extrabold text-2xl uppercase tracking-tight">Weekly Review</h1>
    <p class="text-[#6B6B6B] text-sm mt-1">Last 7 days — how you actually showed up.</p>
</div>

{{-- Top stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $totalWins }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Total Wins</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $weekRate }}%</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Completion Rate</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">🔥 {{ $stat->streak }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Current Streak</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $totalTasks }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Tasks Created</div>
    </div>
</div>

{{-- Daily wins bar --}}
<div class="card mb-6" style="box-shadow: var(--shadow-hard);">
    <div class="text-xs font-bold uppercase tracking-widest text-[#6B6B6B] mb-4">Wins Per Day</div>
    @php $maxWins = max($days->max('wins'), 1); @endphp
    <div class="flex items-end gap-2 h-28">
        @foreach($days as $day)
            <div class="flex-1 flex flex-col items-center gap-1">
                <span class="font-mono text-[11px] font-bold">{{ $day['wins'] ?: '' }}</span>
                <div class="w-full rounded-t-[3px] transition-all" style="height: {{ max(($day['wins'] / $maxWins) * 80, $day['wins'] > 0 ? 8 : 2) }}px; background: {{ $day['wins'] > 0 ? '#FF4F00' : '#E8E8E0' }};"></div>
                <span class="text-[11px] text-[#6B6B6B] font-bold">{{ $day['label'] }}</span>
            </div>
        @endforeach
    </div>
</div>

{{-- Completion by section --}}
<div class="card" style="box-shadow: var(--shadow-hard);">
    <div class="text-xs font-bold uppercase tracking-widest text-[#6B6B6B] mb-4">Completion by Section</div>
    @php
        $sectionMeta = [
            'must'   => ['label' => 'Must Do',   'color' => '#FF4F00'],
            'should' => ['label' => 'Should Do', 'color' => '#FFC900'],
            'good'   => ['label' => 'Good To Do','color' => '#23A094'],
            'park'   => ['label' => 'Parking Lot','color' => '#B0B0A8'],
        ];
    @endphp
    <div class="space-y-4">
        @foreach($sectionStats as $key => $stat)
            <div>
                <div class="flex justify-between items-center mb-1">
                    <span class="text-[13px] font-bold">{{ $sectionMeta[$key]['label'] }}</span>
                    <span class="font-mono text-[12px] text-[#6B6B6B]">{{ $stat['done'] }}/{{ $stat['total'] }} · {{ $stat['rate'] }}%</span>
                </div>
                <div class="h-2 bg-[#E8E8E0] rounded-full overflow-hidden">
                    <div class="h-full rounded-full" style="width: {{ $stat['rate'] }}%; background: {{ $sectionMeta[$key]['color'] }};"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
