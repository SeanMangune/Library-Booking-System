<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'QCU Library Booking System')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Inter', system-ui, sans-serif; }
        .sidebar-link {
            position: relative;
            overflow: hidden;
        }
        .sidebar-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: transparent;
            transition: all 0.3s ease;
        }
        .sidebar-link.active::before {
            background: linear-gradient(180deg, #60A5FA 0%, #3B82F6 100%);
        }
        .sidebar-link.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(29, 78, 216, 0.1) 100%);
            color: white;
            border-radius: 0.5rem;
        }
        .sidebar-link.active svg {
            color: #60A5FA;
        }
        .sidebar-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.05);
        }
        .fc-event {
            cursor: pointer;
            border-radius: 6px !important;
            font-weight: 500 !important;
        }
        .fc-daygrid-day.fc-day-today {
            background: linear-gradient(135deg, #FEF9C3 0%, #FDE68A 100%) !important;
        }
        .fc-toolbar-title {
            font-weight: 700 !important;
        }
        .fc-button-primary {
            background: linear-gradient(135deg, #4F46E5 0%, #4338CA 100%) !important;
            border: none !important;
            font-weight: 500 !important;
            border-radius: 8px !important;
        }
        .fc-button-primary:hover {
            background: linear-gradient(135deg, #4338CA 0%, #3730A3 100%) !important;
        }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        /* Smooth transitions */
        .transition-smooth { transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); }
        /* Card hover effects */
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.1); }
        /* Gradient text */
        .gradient-text { background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 text-gray-900 antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 transform transition-transform duration-300 lg:translate-x-0 shadow-2xl"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <!-- Logo -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-white/10 bg-gradient-to-r from-indigo-900/50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-white font-bold text-lg tracking-tight">QCU Library</span>
                        <p class="text-indigo-300 text-xs">Booking System</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-white hover:bg-white/10 p-1.5 rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3 space-y-1">
                <p class="px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">Main Menu</p>
                <a href="{{ route('dashboard') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('rooms.index') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Manage Rooms</span>
                </a>

                <a href="{{ route('reservations.index') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Reservations</span>
                </a>

                <a href="{{ route('approvals.index') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Approvals</span>
                    @php $pendingCount = \App\Models\Booking::where('status', 'pending')->count(); @endphp
                    @if($pendingCount > 0)
                    <span class="ml-auto bg-gradient-to-r from-red-500 to-rose-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg shadow-red-500/30 animate-pulse">{{ $pendingCount }}</span>
                    @endif
                </a>

                <a href="{{ route('calendar.index') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span>Calendar</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 lg:ml-64">
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true" class="lg:hidden text-gray-600 hover:text-gray-900">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <!-- Breadcrumb -->
                        <nav class="hidden sm:flex items-center gap-2 text-sm">
                            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            </a>
                            @yield('breadcrumb')
                        </nav>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Notifications Dropdown -->
                        <div x-data="{ notifOpen: false }" class="relative">
                            <button @click="notifOpen = !notifOpen" class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @php $pendingNotifCount = \App\Models\Booking::where('status', 'pending')->count(); @endphp
                                @if($pendingNotifCount > 0)
                                <span class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-xs min-w-[18px] h-[18px] flex items-center justify-center rounded-full font-bold animate-pulse">{{ $pendingNotifCount }}</span>
                                @endif
                            </button>
                            <div x-show="notifOpen" @click.away="notifOpen = false" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden z-50">
                                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-white font-semibold">Notifications</h3>
                                        @if($pendingNotifCount > 0)
                                        <span class="bg-white/20 text-white text-xs px-2 py-1 rounded-full">{{ $pendingNotifCount }} pending</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="max-h-80 overflow-y-auto">
                                    @php $recentPending = \App\Models\Booking::where('status', 'pending')->with('room')->latest()->take(5)->get(); @endphp
                                    @forelse($recentPending as $notif)
                                    <a href="{{ route('approvals.index') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                        <div class="flex items-start gap-3">
                                            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $notif->room->name ?? 'Room' }}</p>
                                                <p class="text-xs text-gray-500">{{ $notif->user_name }} requested booking</p>
                                                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                            </div>
                                        </div>
                                    </a>
                                    @empty
                                    <div class="px-4 py-8 text-center">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                        </svg>
                                        <p class="text-sm text-gray-500">No pending notifications</p>
                                    </div>
                                    @endforelse
                                </div>
                                @if($pendingNotifCount > 0)
                                <a href="{{ route('approvals.index') }}" class="block bg-gray-50 px-4 py-3 text-center text-sm font-medium text-indigo-600 hover:text-indigo-700 hover:bg-gray-100 transition-colors">
                                    View all pending approvals
                                </a>
                                @endif
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-gray-900 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shadow-md">
                                    QA
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="font-semibold text-gray-800">QCU Librarian</p>
                                    <p class="text-xs text-gray-500">Administrator</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-200 py-2 z-50">
                                <div class="px-4 py-3 border-b border-gray-100">
                                    <p class="text-sm font-semibold text-gray-800">QCU Librarian Admin</p>
                                    <p class="text-xs text-gray-500">admin@qcu.edu.ph</p>
                                </div>
                                <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    My Profile
                                </a>
                                <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    Settings
                                </a>
                                <hr class="my-2">
                                <a href="#" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign Out
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6">
                @yield('content')
            </main>
        </div>

        <!-- Overlay for mobile sidebar -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
             class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"></div>
    </div>

    @stack('scripts')
</body>
</html>
