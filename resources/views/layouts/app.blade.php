<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Daily Win Board')</title>

    {{-- Open Graph --}}
    <meta property="og:title" content="Daily Win Board">
    <meta property="og:description" content="A commitment-first, dopamine-driven daily board. Lock in your one non-negotiable, prioritise ruthlessly, and track your wins.">
    <meta property="og:image" content="{{ asset('images/og.png') }}">
    <meta property="og:url" content="https://life.drtakaful.com">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Daily Win Board">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Daily Win Board">
    <meta name="twitter:description" content="A commitment-first, dopamine-driven daily board. Lock in your one non-negotiable, prioritise ruthlessly, and track your wins.">
    <meta name="twitter:image" content="{{ asset('images/og.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;700&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F4F4F0]">

<nav class="bg-white border-b-2 border-black">
    {{-- Top row: brand + avatar/logout --}}
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between">
        <span class="font-display text-lg font-black tracking-tight">DAILY WIN BOARD</span>
        @auth
        <div class="flex items-center gap-3">
            {{-- Desktop nav links --}}
            <div class="hidden sm:flex items-center gap-4">
                <a href="{{ route('dashboard') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('dashboard') ? 'underline' : 'text-[#6B6B6B]' }}">Board</a>
                <a href="{{ route('review') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('review') ? 'underline' : 'text-[#6B6B6B]' }}">Review</a>
                <a href="{{ route('history') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('history') ? 'underline' : 'text-[#6B6B6B]' }}">History</a>
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.index') }}" class="text-sm font-bold text-[#FF4F00] hover:underline {{ request()->routeIs('admin.*') ? 'underline' : '' }}">⚙ Admin</a>
                @endif
            </div>
            @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" class="w-7 h-7 rounded-full border-2 border-black" alt="">
            @endif
            <span class="hidden sm:inline text-sm font-medium">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-[#6B6B6B] hover:text-black font-medium">Logout</button>
            </form>
        </div>
        @endauth
    </div>
    {{-- Mobile-only nav links row --}}
    @auth
    <div class="sm:hidden flex items-center gap-5 px-4 py-2 border-t border-[#E8E8E0]">
        <a href="{{ route('dashboard') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('dashboard') ? 'underline' : 'text-[#6B6B6B]' }}">Board</a>
        <a href="{{ route('review') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('review') ? 'underline' : 'text-[#6B6B6B]' }}">Review</a>
        <a href="{{ route('history') }}" class="text-sm font-bold hover:underline {{ request()->routeIs('history') ? 'underline' : 'text-[#6B6B6B]' }}">History</a>
        @if(auth()->user()->is_admin)
            <a href="{{ route('admin.index') }}" class="text-sm font-bold text-[#FF4F00] hover:underline {{ request()->routeIs('admin.*') ? 'underline' : '' }}">⚙ Admin</a>
        @endif
    </div>
    @endauth
</nav>

<main class="max-w-6xl mx-auto px-4 py-8">
    @yield('content')
</main>

<div id="toast" class="hidden fixed top-4 right-4 bg-white border-2 border-black rounded-[6px] px-5 py-4 font-bold text-[15px] z-50" style="box-shadow: 4px 4px 0 #000;"></div>

@yield('scripts')
</body>
</html>
