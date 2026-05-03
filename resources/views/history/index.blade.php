@extends('layouts.app')

@section('title', 'Win History — Daily Win Board')

@section('content')
<div class="mb-6">
    <h1 class="font-display font-extrabold text-2xl uppercase tracking-tight">Win History</h1>
    <p class="text-[#6B6B6B] text-sm mt-1">Completed weeks — how you showed up, week by week.</p>
</div>

@if($weeks->isEmpty())
    <div class="card text-center py-12" style="box-shadow: var(--shadow-hard);">
        <div class="text-2xl mb-3">🏆</div>
        <p class="font-bold text-lg">No completed weeks yet.</p>
        <p class="text-[#6B6B6B] text-sm mt-1">Come back once your first full week is done.</p>
    </div>
@else
    <div class="space-y-4">
        @foreach($weeks as $week)
        <div class="card" style="box-shadow: var(--shadow-hard);">
            {{-- Week header --}}
            <div class="flex items-center justify-between mb-4">
                <span class="font-display font-extrabold text-[14px] uppercase tracking-wider">{{ $week['label'] }}</span>
                <span class="bg-black text-white text-[11px] font-bold px-2 py-0.5 rounded-[3px] font-mono">{{ $week['total_wins'] }} wins</span>
            </div>

            {{-- Overall completion --}}
            <div class="mb-4">
                <div class="flex justify-between items-center mb-1">
                    <span class="text-[12px] font-bold text-[#6B6B6B] uppercase tracking-wide">Overall</span>
                    <span class="font-mono text-[12px] font-bold">{{ $week['total_wins'] }}/{{ $week['total_tasks'] }} · {{ $week['week_rate'] }}%</span>
                </div>
                <div class="h-2.5 bg-[#E8E8E0] rounded-full overflow-hidden">
                    <div class="h-full rounded-full bg-black" style="width: {{ $week['week_rate'] }}%;"></div>
                </div>
            </div>

            {{-- Section breakdown --}}
            <div class="space-y-2">
                @foreach($week['section_stats'] as $key => $stat)
                    @if($stat['total'] > 0)
                    <div class="flex items-center gap-3">
                        <span class="text-[12px] font-bold w-14 shrink-0" style="color: {{ $stat['color'] }};">{{ $stat['label'] }}</span>
                        <div class="flex-1 h-1.5 bg-[#E8E8E0] rounded-full overflow-hidden">
                            <div class="h-full rounded-full" style="width: {{ $stat['rate'] }}%; background: {{ $stat['color'] }};"></div>
                        </div>
                        <span class="font-mono text-[11px] text-[#6B6B6B] w-16 text-right shrink-0">{{ $stat['done'] }}/{{ $stat['total'] }} · {{ $stat['rate'] }}%</span>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
@endif
@endsection
