@extends('layouts.app')

@section('title', 'Admin — Daily Win Board')

@section('content')
<div class="mb-6">
    <h1 class="font-display font-extrabold text-2xl uppercase tracking-tight">Admin Dashboard</h1>
    <p class="text-[#6B6B6B] text-sm mt-1">System overview — today's activity and all users.</p>
</div>

{{-- System stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $totalUsers }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Total Users</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $dailyActiveUsers }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Active Today</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $tasksCreatedToday }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Tasks Today</div>
    </div>
    <div class="card text-center py-4" style="box-shadow: var(--shadow-hard-sm);">
        <div class="font-mono font-bold text-3xl">{{ $winsToday }}</div>
        <div class="text-xs text-[#6B6B6B] uppercase tracking-wide mt-1">Wins Today</div>
    </div>
</div>

{{-- User table --}}
<div class="card" style="box-shadow: var(--shadow-hard);">
    <div class="text-xs font-bold uppercase tracking-widest text-[#6B6B6B] mb-4">All Users</div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b-2 border-black">
                    <th class="text-left pb-3 font-bold text-[12px] uppercase tracking-wider">User</th>
                    <th class="text-center pb-3 font-bold text-[12px] uppercase tracking-wider">Streak</th>
                    <th class="text-center pb-3 font-bold text-[12px] uppercase tracking-wider">Total Wins</th>
                    <th class="text-center pb-3 font-bold text-[12px] uppercase tracking-wider">Today</th>
                    <th class="text-center pb-3 font-bold text-[12px] uppercase tracking-wider">Role</th>
                    <th class="text-center pb-3 font-bold text-[12px] uppercase tracking-wider">Joined</th>
                    <th class="pb-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E8E8E0]">
                @foreach($users as $user)
                    <tr class="{{ $user->id === auth()->id() ? 'bg-[#FFF9E6]' : '' }}">
                        <td class="py-3">
                            <div class="flex items-center gap-2">
                                @if($user->avatar)
                                    <img src="{{ $user->avatar }}" class="w-7 h-7 rounded-full border border-black" alt="">
                                @endif
                                <div>
                                    <div class="font-bold text-[13px]">{{ $user->name }} @if($user->id === auth()->id())<span class="text-[#6B6B6B] font-normal">(you)</span>@endif</div>
                                    <div class="text-[11px] text-[#6B6B6B]">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 text-center font-mono font-bold">🔥 {{ $user->streak }}</td>
                        <td class="py-3 text-center font-mono font-bold">{{ $user->total_wins }}</td>
                        <td class="py-3 text-center font-mono text-[13px]">{{ $user->wins_today }}W / {{ $user->tasks_today }}T</td>
                        <td class="py-3 text-center">
                            @if($user->is_admin)
                                <span class="text-[11px] font-bold bg-[#FF4F00] text-white px-2 py-0.5 rounded-[3px]">Admin</span>
                            @else
                                <span class="text-[11px] font-bold bg-[#E8E8E0] text-[#6B6B6B] px-2 py-0.5 rounded-[3px]">User</span>
                            @endif
                        </td>
                        <td class="py-3 text-center text-[12px] text-[#6B6B6B]">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="py-3 text-right">
                            @if($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.toggle', $user) }}">
                                    @csrf
                                    <button type="submit" class="text-[11px] font-bold border border-black rounded px-2 py-1 hover:bg-black hover:text-white transition-all">
                                        {{ $user->is_admin ? 'Revoke Admin' : 'Make Admin' }}
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
