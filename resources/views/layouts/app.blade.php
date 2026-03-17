<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/smartspace-logo.png') }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/smartspace-logo.svg') }}">
    <title>@yield('title', 'SmartSpace')</title>
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
            border: 1px solid transparent;
            backdrop-filter: blur(0px);
            transition: transform 0.25s ease, background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease;
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
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.25) 0%, rgba(37, 99, 235, 0.12) 100%);
            color: white;
            border-radius: 0.5rem;
            border-color: rgba(99, 102, 241, 0.32);
            box-shadow: 0 12px 28px -18px rgba(99, 102, 241, 0.9);
        }
        .sidebar-link.active svg {
            color: #60A5FA;
        }
        .sidebar-link:hover:not(.active) {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(129, 140, 248, 0.24);
            transform: translateX(4px);
        }
        .sidebar-link:hover svg {
            transform: scale(1.08);
            transition: transform 0.22s ease;
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
        .sidebar-brand,
        .sidebar-section-label,
        .sidebar-text,
        .sidebar-badge {
            overflow: hidden;
            white-space: nowrap;
            opacity: 1;
            transform: translateX(0) scale(1);
            transition: opacity 180ms ease, transform 300ms cubic-bezier(0.22, 1, 0.36, 1), max-width 300ms cubic-bezier(0.22, 1, 0.36, 1), max-height 300ms cubic-bezier(0.22, 1, 0.36, 1), margin 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .sidebar-brand { max-width: 14rem; }
        .sidebar-section-label { max-height: 1rem; }
        .sidebar-text { max-width: 10rem; }
        .sidebar-badge { max-width: 3.5rem; }
        .sidebar-link .sidebar-badge-collapsed {
            position: absolute;
            top: 0.35rem;
            right: 0.75rem;
            opacity: 0;
            transform: translateY(-4px) scale(0.78);
            pointer-events: none;
            animation: none;
            transition: opacity 180ms ease, transform 260ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        @media (min-width: 1024px) {
            aside.sidebar-collapsed .sidebar-brand,
            aside.sidebar-collapsed .sidebar-section-label,
            aside.sidebar-collapsed .sidebar-text,
            aside.sidebar-collapsed .sidebar-badge {
                opacity: 0;
                transform: translateX(-8px) scale(0.96);
                pointer-events: none;
            }
            aside.sidebar-collapsed .sidebar-brand,
            aside.sidebar-collapsed .sidebar-text,
            aside.sidebar-collapsed .sidebar-badge {
                max-width: 0;
            }
            aside.sidebar-collapsed .sidebar-section-label {
                max-height: 0;
                margin-bottom: 0;
                transform: translateY(-4px) scale(0.96);
            }
            aside.sidebar-collapsed .sidebar-link {
                justify-content: center;
                gap: 0;
                padding-left: 0;
                padding-right: 0;
            }
            aside.sidebar-collapsed .sidebar-link .fa-icon {
                transform: scale(1.08);
                margin-left: auto;
                margin-right: auto;
            }
            aside.sidebar-collapsed .sidebar-header {
                justify-content: center;
                padding-left: 0;
                padding-right: 0;
            }
            aside.sidebar-collapsed .sidebar-header > .flex {
                width: 100%;
                justify-content: center;
                gap: 0;
            }
            aside:not(.sidebar-collapsed) .sidebar-link:hover .fa-icon {
                transform: translateX(1px);
            }
            aside.sidebar-collapsed .sidebar-link .sidebar-badge-collapsed {
                opacity: 1;
                transform: translateY(0) scale(1);
                animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            }
            aside.sidebar-collapsed .sidebar-link .sidebar-badge-expanded {
                display: none !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 text-gray-900 antialiased">
    @php
        $currentUser = auth()->user();
        $isStaff = $currentUser?->isStaff() ?? false;
        $isAdmin = $currentUser?->isAdmin() ?? false;
        $hasNotificationsTable = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $pendingApprovalCount = $isStaff ? \App\Models\Booking::where('status', 'pending')->count() : 0;
        $recentPendingApprovals = $isStaff
            ? \App\Models\Booking::where('status', 'pending')->with('room')->latest()->take(5)->get()
            : collect();
        $userUnreadNotifications = ($currentUser && $hasNotificationsTable)
            ? $currentUser->unreadNotifications()->latest()->take(8)->get()
            : collect();
        $userUnreadCount = $userUnreadNotifications->count();
        $headerNotificationCount = $pendingApprovalCount + $userUnreadCount;
        $initials = $currentUser
            ? collect(preg_split('/\s+/', trim($currentUser->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
            : 'U';
    @endphp
    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="fixed inset-y-0 left-0 z-50 w-64 overflow-hidden bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 transform transition-transform duration-300 lg:translate-x-0 shadow-2xl"
               :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
            <div class="pointer-events-none absolute -top-12 -left-14 h-40 w-40 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute top-52 -right-16 h-44 w-44 rounded-full bg-fuchsia-500/15 blur-3xl"></div>
            <!-- Logo -->
            <div class="flex items-center justify-between h-20 px-4 border-b border-white/10 bg-gradient-to-r from-indigo-900/50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="relative h-12 w-12 flex items-center justify-center">
                        <span class="absolute inset-0 rounded-full bg-indigo-400/25 blur-md"></span>
                        <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="relative h-12 w-12 object-contain drop-shadow-[0_0_20px_rgba(129,140,248,0.55)]" onerror="this.onerror=null;this.src='{{ asset('images/smartspace-mark.svg') }}';">
                    </div>
                    <div>
                        <span class="text-white font-bold text-lg tracking-tight">SmartSpace</span>
                        <p class="text-indigo-300 text-xs">Reservation System</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" class="lg:hidden text-white hover:bg-white/10 p-1.5 rounded-lg transition-colors">
<i class="w-5 h-5 fa-icon fa-solid fa-xmark text-base leading-none"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3 space-y-1">
                <p class="px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">Main Menu</p>
                <a href="{{ route('dashboard') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-solid fa-house text-base leading-none"></i>
                    <span>Dashboard</span>
                </a>

                @if($isStaff)
                    <a href="{{ route('rooms.index') }}" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-solid fa-building text-base leading-none"></i>
                        <span>Manage Rooms</span>
                    </a>

                    <a href="{{ route('reservations.index') }}" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-regular fa-calendar text-base leading-none"></i>
                        <span>Reservations</span>
                    </a>

                    <a href="{{ route('approvals.index') }}" 
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-solid fa-circle-check text-base leading-none"></i>
                        <span>Approvals</span>
                        @php $pendingCount = \App\Models\Booking::where('status', 'pending')->count(); @endphp
                        @if($pendingCount > 0)
                        <span class="ml-auto bg-gradient-to-r from-red-500 to-rose-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg shadow-red-500/30 animate-pulse">{{ $pendingCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('reports.index') }}"
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('reports.*') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-solid fa-chart-column text-base leading-none"></i>
                        <span>Reports</span>
                    </a>
                @endif

                <a href="{{ route('calendar.index') }}" 
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
<i class="w-5 h-5 fa-icon fa-regular fa-calendar text-base leading-none"></i>
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
<i class="w-6 h-6 fa-icon fa-solid fa-bars text-lg leading-none"></i>
                        </button>
                        <!-- Breadcrumb -->
                        <nav class="hidden sm:flex items-center gap-2 text-sm">
                            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">
<i class="w-4 h-4 fa-icon fa-solid fa-house text-sm leading-none"></i>
                            </a>
                            @yield('breadcrumb')
                        </nav>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Notifications Dropdown -->
                        <div x-data="{ notifOpen: false }"
                             id="header-notification-root"
                             data-user-id="{{ $currentUser?->id }}"
                             data-unread-url="{{ route('notifications.unread') }}"
                             data-approvals-url="{{ route('approvals.index') }}"
                             class="relative">
                            <button @click="notifOpen = !notifOpen" class="relative p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition-colors">
                                <i class="w-6 h-6 fa-icon fa-solid fa-bell text-2xl leading-none"></i>
                                <span data-role="header-notification-badge"
                                      class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-xs min-w-[18px] h-[18px] flex items-center justify-center rounded-full font-bold animate-pulse {{ $headerNotificationCount > 0 ? '' : 'hidden' }}">{{ $headerNotificationCount }}</span>
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
                                        <span data-role="header-unread-chip"
                                              class="bg-white/20 text-white text-xs px-2 py-1 rounded-full {{ $headerNotificationCount > 0 ? '' : 'hidden' }}">{{ $headerNotificationCount }} unread</span>
                                    </div>
                                </div>

                                <div class="max-h-80 overflow-y-auto">
                                    @if($isStaff)
                                        <div data-role="pending-approvals-section">
                                            <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500 bg-gray-50 border-b border-gray-100">Pending Approvals</div>
                                            <div data-role="pending-approvals-list">
                                                @forelse($recentPendingApprovals as $notif)
                                                <a href="{{ route('approvals.index') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                                    <div class="flex items-start gap-3">
                                                        <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                                                            <i class="w-5 h-5 text-amber-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <p class="text-sm font-medium text-gray-900 truncate">{{ $notif->room->name ?? 'Room' }}</p>
                                                            <p class="text-xs text-gray-500">{{ $notif->user_name }} requested booking</p>
                                                            <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                                        </div>
                                                    </div>
                                                </a>
                                                @empty
                                                <div class="px-4 py-3 border-b border-gray-100">
                                                    <p class="text-sm text-gray-500">No pending approvals right now.</p>
                                                </div>
                                                @endforelse
                                            </div>
                                        </div>
                                    @endif

                                    <div class="px-4 py-2 text-[11px] font-semibold uppercase tracking-wider text-gray-500 bg-gray-50 border-b border-gray-100">Your Unread</div>
                                    <div data-role="user-unread-list">
                                        @forelse($userUnreadNotifications as $notification)
                                        <a href="{{ $notification->data['url'] ?? '#' }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
                                            <p class="text-sm font-medium text-gray-900">{{ $notification->data['title'] ?? 'Notification' }}</p>
                                            <p class="text-xs text-gray-600 mt-1">{{ $notification->data['message'] ?? '' }}</p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                        </a>
                                        @empty
                                        <div class="px-4 py-8 text-center">
                                            <i class="w-12 h-12 text-gray-300 mx-auto mb-2 fa-icon fa-solid fa-inbox text-5xl leading-none"></i>
                                            <p class="text-sm text-gray-500">No unread notifications</p>
                                        </div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="bg-gray-50 px-4 py-3 flex items-center justify-between gap-2">
                                    @if($isStaff)
                                    <a href="{{ route('approvals.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Pending approvals</a>
                                    @else
                                    <span class="text-sm text-gray-500">Up to date</span>
                                    @endif

                                    <div data-role="mark-all-read-container" class="{{ $userUnreadCount > 0 ? '' : 'hidden' }}">
                                        <form method="POST" action="{{ route('notifications.mark-all-read') }}" data-role="mark-all-read-form">
                                            @csrf
                                            <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Mark all as read</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div x-data="{ open: false, logoutOpen: false }" class="relative">
                            <button @click="open = !open" class="flex items-center gap-3 text-sm font-medium text-gray-700 hover:text-gray-900 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold shadow-md">
                                    {{ $initials }}
                                </div>
                                <div class="hidden sm:block text-left">
                                    <p class="font-semibold text-gray-800">{{ $currentUser?->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $currentUser?->roleLabel() ?? 'User' }}</p>
                                </div>
                                
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
                                    <p class="text-sm font-semibold text-gray-800">{{ $currentUser?->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $currentUser?->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
<i class="w-4 h-4 text-gray-400 fa-icon fa-regular fa-user text-sm leading-none"></i>
                                    My Profile
                                </a>
                                @if($isStaff)
                                    <a href="{{ route('settings.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-gear text-sm leading-none"></i>
                                        Settings
                                    </a>
                                @endif
                                <hr class="my-2">
                                <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="px-2" @submit.prevent="logoutOpen = true; open = false">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center gap-3 px-2 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors rounded-lg">
<i class="w-4 h-4 fa-icon fa-solid fa-right-from-bracket text-sm leading-none"></i>
                                        Sign Out
                                    </button>
                                </form>
                            </div>

                            <!-- Logout Confirmation Modal -->
                            <div x-show="logoutOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4">
                                <div class="absolute inset-0 bg-black/30 backdrop-blur-sm" @click="logoutOpen = false"></div>
                                <div class="relative w-full max-w-md">
                                    <div class="relative group">
                                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-10 -bottom-10 h-16 bg-gradient-to-r from-indigo-500 via-purple-500 to-teal-500 blur-3xl opacity-30"></div>
                                        <div class="bg-gradient-to-b from-white to-slate-50 rounded-3xl border border-gray-200 shadow-2xl max-h-[88vh] overflow-hidden flex flex-col">
                                    <div class="px-6 py-5 border-b border-gray-100 bg-white/60 flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Logout</h3>
                                            <p class="text-sm text-gray-500 mt-1">Are you sure you want to logout?</p>
                                        </div>
                                        <button type="button" @click="logoutOpen = false" class="px-3 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                                            Close
                                        </button>
                                    </div>
                                    <div class="p-6 flex items-center justify-end gap-3 flex-1 min-h-0 overflow-y-auto">
                                        <button type="button" @click="logoutOpen = false" class="px-4 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm font-semibold text-gray-800 transition-colors">
                                            Cancel
                                        </button>
                                        <button type="button" @click="$refs.logoutForm.submit()" class="px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-semibold transition-colors">
                                            Logout
                                        </button>
                                    </div>
                                        </div>
                                    </div>
                                </div>
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
               class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40 lg:hidden"></div>
    </div>

    @stack('scripts')
</body>
</html>

