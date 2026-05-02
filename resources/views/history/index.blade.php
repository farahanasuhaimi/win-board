@extends('layouts.app')

@section('title', 'Win History — Daily Win Board')

@section('content')
<div class="mb-6">
    <h1 class="font-display font-extrabold text-2xl uppercase tracking-tight">Win History</h1>
    <p class="text-[#6B6B6B] text-sm mt-1">Every task you've completed, with the time you did it.</p>
</div>

@if($history->isEmpty())
    <div class="card text-center py-12" style="box-shadow: var(--shadow-hard);">
        <div class="text-4xl mb-3">🏆</div>
        <p class="font-bold text-lg">No wins yet.</p>
        <p class="text-[#6B6B6B] text-sm mt-1">Complete tasks on your board and they'll appear here.</p>
    </div>
@else
    @foreach($history as $date => $tasks)
        <div class="mb-6">
            <div class="flex items-center gap-3 mb-3">
                <span class="font-display font-extrabold text-[13px] uppercase tracking-widest">
                    {{ \Carbon\Carbon::parse($date)->isToday() ? 'Today' : (\Carbon\Carbon::parse($date)->isYesterday() ? 'Yesterday' : \Carbon\Carbon::parse($date)->format('D, d M Y')) }}
                </span>
                <span class="bg-black text-white text-[11px] font-bold px-2 py-0.5 rounded-[3px] font-mono">{{ $tasks->count() }} wins</span>
            </div>

            <div class="card" style="box-shadow: var(--shadow-hard);">
                <ul class="divide-y divide-[#E8E8E0]">
                    @foreach($tasks as $task)
                        @php
                            $sectionColors = ['must' => '#FF4F00', 'should' => '#FFC900', 'good' => '#23A094', 'park' => '#B0B0A8'];
                            $sectionLabels = ['must' => 'Must', 'should' => 'Should', 'good' => 'Good', 'park' => 'Park'];
                        @endphp
                        <li class="flex items-center gap-3 py-3">
                            <svg class="flex-shrink-0 w-4 h-4" viewBox="0 0 12 12"><circle cx="6" cy="6" r="5" fill="{{ $sectionColors[$task->section] ?? '#000' }}"/><path d="M3 6l2 2 4-4" stroke="#fff" stroke-width="1.5" fill="none"/></svg>
                            <span class="flex-1 text-[14px] line-through text-[#6B6B6B]">{{ $task->text }}</span>
                            <span class="text-[11px] font-bold px-2 py-0.5 rounded-[3px]" style="background: {{ $sectionColors[$task->section] ?? '#000' }}1a; color: {{ $sectionColors[$task->section] ?? '#000' }}">
                                {{ $sectionLabels[$task->section] ?? $task->section }}
                            </span>
                            <span class="text-[12px] text-[#6B6B6B] font-mono shrink-0">
                                {{ \Carbon\Carbon::parse($task->done_at)->format('h:i A') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endforeach
@endif
@endsection
