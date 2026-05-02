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

<nav class="bg-white border-b-2 border-black px-6 py-4 flex items-center justify-between">
    <span class="font-display text-xl font-black tracking-tight">DAILY WIN BOARD</span>
    <div class="flex items-center gap-4">
        @auth
            <div class="flex items-center gap-3">
                @if(auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" class="w-8 h-8 rounded-full border-2 border-black" alt="">
                @endif
                <span class="text-sm font-medium">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-[#6B6B6B] hover:text-black font-medium">Logout</button>
                </form>
            </div>
        @endauth
    </div>
</nav>

<main class="max-w-6xl mx-auto px-4 py-8">
    @yield('content')
</main>

<div id="toast" class="hidden fixed top-4 right-4 bg-white border-2 border-black rounded-[6px] px-5 py-4 font-bold text-[15px] z-50" style="box-shadow: 4px 4px 0 #000;"></div>

@yield('scripts')
</body>
</html>
