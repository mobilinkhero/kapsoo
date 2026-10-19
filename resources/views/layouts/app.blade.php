<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Kapso WhatsApp')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', ui-sans-serif, system-ui, sans-serif; }
        .fade-in { animation: fadeIn .5s cubic-bezier(.2,.8,.2,1); }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        .slide-up { animation: slideUp .45s cubic-bezier(.2,.8,.2,1); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
        .glass { background: rgba(255,255,255,.7); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); }
        .card-hover { transition: transform .2s ease, box-shadow .2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(15,23,42,.12); }
        .nav-active { background: linear-gradient(90deg, rgba(16,185,129,.15), transparent); border-left: 3px solid #10b981; }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 6px; }
        
        /* Modern Sidebar with Expandable Groups */
        .sidebar-label { transition: opacity .2s ease, transform .2s ease; }
        .sidebar-badge { transition: opacity .15s ease, transform .15s ease; }
        .nav-group-header { transition: all .2s ease; }
        .nav-group-content { transition: max-height .3s cubic-bezier(.4,0,.2,1), opacity .2s ease; }
        
        /* Sidebar collapsed state */
        html.sidebar-collapsed .sidebar { width: 4.5rem !important; }
        html.sidebar-collapsed .sidebar-label { opacity: 0; transform: translateX(-8px); pointer-events: none; }
        html.sidebar-collapsed .sidebar-badge { opacity: 0; transform: scale(0); }
        html.sidebar-collapsed .nav-group-header { justify-content: center; padding-left: .75rem; padding-right: .75rem; }
        html.sidebar-collapsed .nav-group-content { display: none !important; }
        html.sidebar-collapsed .sidebar nav > a { justify-content: center; padding-left: .75rem; padding-right: .75rem; }
        html.sidebar-collapsed .sidebar .brand-area { justify-content: center; padding-left: .75rem; padding-right: .75rem; }
        html.sidebar-collapsed .sidebar .user-info { display: none; }
        html.sidebar-collapsed .sidebar .logout-text { display: none; }
        html.sidebar-collapsed main { padding-left: 4.5rem !important; }
        
        /* Hover tooltip for collapsed sidebar */
        .nav-tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 0.75rem;
            padding: 0.5rem 0.75rem;
            background: #1e293b;
            color: white;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity .2s ease;
            z-index: 100;
        }
        html.sidebar-collapsed .nav-item:hover .nav-tooltip,
        html.sidebar-collapsed .nav-group-header:hover .nav-tooltip {
            opacity: 1;
        }
    </style>
</head>
<body class="h-full bg-slate-100 text-slate-800 antialiased">
@php $isInboxPage = request()->routeIs('conversations.*'); @endphp
<script>(function(){try{if(localStorage.getItem('appSidebarCollapsed')==='1'){document.documentElement.classList.add('sidebar-collapsed');}}catch(e){}})();</script>
<div x-data="{ 
    sidebarOpen: false, 
    mobileNav: false,
    expandedGroups: $persist(['main']).as('sidebar_expanded_groups')
}" class="min-h-full">
    <!-- Mobile top bar -->
    <header class="lg:hidden sticky top-0 z-40 glass border-b border-slate-200/60 px-4 py-3 flex items-center justify-between">
        <button @click="sidebarOpen = true" class="p-2 rounded-xl hover:bg-slate-200/60 transition">
            <svg class="w-6 h-6 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white font-bold text-sm">K</div>
            <span class="font-bold text-slate-900">@yield('title', 'Kapso')</span>
        </div>
        <div class="w-10"></div>
    </header>

    <!-- Sidebar overlay -->
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40 lg:hidden" x-transition.opacity></div>

    <!-- Sidebar -->
    <aside class="sidebar fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-slate-300 transform transition-all duration-300 ease-out flex flex-col" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <!-- Brand -->
        <div class="brand-area h-18 flex items-center px-6 py-5 border-b border-slate-800">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold text-lg shadow-lg shadow-emerald-500/30 shrink-0">K</div>
                <div class="sidebar-label min-w-0">
                    <p class="font-bold text-white text-lg leading-tight">Kapso</p>
                    <p class="text-xs text-slate-500">WhatsApp Suite</p>
                </div>
            </div>
            <button type="button" onclick="toggleSidebarCollapse()" class="ml-auto p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 shrink-0 hidden lg:block" title="Toggle sidebar">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
            </button>
            <button @click="sidebarOpen = false" class="ml-auto lg:hidden p-1.5 rounded-lg hover:bg-slate-800 text-slate-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Nav -->
        <nav class="flex-1 p-4 space-y-2 overflow-y-auto scrollbar-thin">
            @php
                $navGroups = [
                    'main' => [
                        'label' => 'Main',
                        'icon' => 'M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z',
                        'items' => [
                            ['Dashboard', route('dashboard'), 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                            ['Inbox', route('conversations.index'), 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z', 3],
                        ]
                    ],
                    'messaging' => [
                        'label' => 'Messaging',
                        'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                        'items' => [
                            ['Contacts', route('contacts.index'), 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                            ['Templates', route('templates.index'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                            ['Broadcasts', route('broadcasts.index'), 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13.47a4.5 4.5 0 000-2.94M19 18a7.5 7.5 0 000-12M13 6.5a3 3 0 00-3 3v7a3 3 0 003 3'],
                        ]
                    ],
                    'automation' => [
                        'label' => 'Automation & Tools',
                        'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
                        'items' => [
                            ['Bot Tester', route('bot-test.index'), 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                            ['Live Chat Test', route('chat-test.index'), 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                            ['Knowledge', route('knowledge.index'), 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ]
                    ],
                    'system' => [
                        'label' => 'System',
                        'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
                        'items' => [
                            ['Settings', route('settings.index'), 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                        ]
                    ],
                ];
                
                // Add conditional items
                if (Route::has('integration.index')) {
                    $navGroups['system']['items'][] = ['Integration', route('integration.index'), 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1'];
                }
                if (Route::has('logs.index')) {
                    $navGroups['system']['items'][] = ['Logs', route('logs.index'), 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'];
                }
            @endphp
            
            @foreach($navGroups as $groupKey => $group)
                <div x-data="{ isExpanded: expandedGroups.includes('{{ $groupKey }}') }" class="nav-group">
                    <!-- Group Header -->
                    <button 
                        @click="isExpanded = !isExpanded; isExpanded ? (expandedGroups.includes('{{ $groupKey }}') || expandedGroups.push('{{ $groupKey }}')) : (expandedGroups = expandedGroups.filter(g => g !== '{{ $groupKey }}'))" 
                        class="nav-group-header relative w-full flex items-center gap-3 px-3 py-2 rounded-lg text-slate-500 hover:text-slate-300 hover:bg-slate-800/40 transition-all duration-200 group"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $group['icon'] }}"/></svg>
                        <span class="text-xs font-semibold uppercase tracking-wider sidebar-label">{{ $group['label'] }}</span>
                        <svg class="w-4 h-4 ml-auto transition-transform duration-200 sidebar-label" :class="isExpanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="nav-tooltip">{{ $group['label'] }}</span>
                    </button>
                    
                    <!-- Group Items -->
                    <div 
                        x-show="isExpanded" 
                        x-collapse.duration.300ms
                        class="nav-group-content mt-1 space-y-0.5"
                    >
                        @foreach($group['items'] as $item)
                            @php 
                                $label = $item[0];
                                $url = $item[1];
                                $icon = $item[2];
                                $badge = $item[3] ?? null;
                                $active = request()->url() === $url || request()->is(trim(parse_url($url, PHP_URL_PATH), '/').'*'); 
                            @endphp
                            <a href="{{ $url }}" class="nav-item relative group flex items-center gap-3 px-3 py-2.5 ml-3 rounded-lg transition-all duration-200 {{ $active ? 'nav-active text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
                                <svg class="w-5 h-5 transition-transform group-hover:scale-110 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                                <span class="font-medium sidebar-label">{{ $label }}</span>
                                @if($badge)
                                    <span class="sidebar-badge ml-auto px-2 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/20 text-emerald-400">{{ $badge }}</span>
                                @endif
                                <span class="nav-tooltip">{{ $label }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <!-- User -->
        <div class="p-4 border-t border-slate-800">
            <div class="user-info mb-2">
                <div class="flex items-center gap-3 px-2 py-2">
                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-semibold text-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0 sidebar-label">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ auth()->user()->email ?? '' }}</p>
                    </div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/60 transition text-sm">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="logout-text">Sign out</span>
                </button>
            </form>
        </div>
    </aside>



    <!-- Main -->
    <main class="min-h-screen lg:pl-72 transition-all duration-300" id="main-content">
        <!-- Top bar -->
        @unless($isInboxPage)
        <header class="sticky top-0 z-30 glass border-b border-slate-200/60">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold text-slate-900">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2 px-3 py-2 rounded-xl bg-white border border-slate-200 text-sm text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <span>Search...</span>
                    </div>
                    <button class="relative p-2 rounded-xl hover:bg-slate-200/60 transition">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500"></span>
                    </button>
                </div>
            </div>
        </header>
        @endunless

        <div class="{{ $isInboxPage ? 'p-0' : 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6' }}">
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)" x-transition class="mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-600 px-5 py-3.5 flex items-center justify-between fade-in">
                    <span>{{ session('success') }}</span>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif
            @if(session('error'))
                <div x-data="{ show: true }" x-show="show" x-transition class="mb-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 px-5 py-3.5 flex items-center justify-between fade-in">
                    <span>{{ session('error') }}</span>
                    <button @click="show = false" class="text-red-500 hover:text-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            @endif
            <div class="fade-in">
                @yield('content')
            </div>
        </div>
    </main>

    <!-- Mobile bottom nav -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 glass border-t border-slate-200/60 px-2 py-1.5 flex justify-around">
        @php
            $mobileNav = [
                ['Dashboard', route('dashboard'), 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                ['Inbox', route('conversations.index'), 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                ['Contacts', route('contacts.index'), 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['More', route('settings.index'), 'M4 6h16M4 12h16M4 18h16'],
            ];
        @endphp
        @foreach($mobileNav as [$label, $url, $icon])
            <a href="{{ $url }}" class="flex flex-col items-center gap-0.5 px-3 py-1.5 rounded-xl text-slate-500 hover:text-emerald-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $icon }}"/></svg>
                <span class="text-[10px] font-medium">{{ $label }}</span>
            </a>
        @endforeach
    </nav>
</div>

    <script>
        function toggleSidebarCollapse() {
            try {
                var isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
                if (isCollapsed) {
                    document.documentElement.classList.remove('sidebar-collapsed');
                    localStorage.setItem('appSidebarCollapsed', '0');
                } else {
                    document.documentElement.classList.add('sidebar-collapsed');
                    localStorage.setItem('appSidebarCollapsed', '1');
                }
            } catch (e) {}
        }
    </script>
</body>
</html>