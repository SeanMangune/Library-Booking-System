<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="apple-touch-icon" href="/images/icons/icon-192.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartSpace')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Inter', system-ui, sans-serif; }
        .sidebar-shell {
            transition: width 380ms cubic-bezier(0.22, 1, 0.36, 1), transform 300ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 260ms ease;
        }
        .content-shell {
            transition: margin-left 380ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .sidebar-link {
            position: relative;
            overflow: hidden;
            transition: background-color 220ms ease, color 220ms ease, gap 300ms cubic-bezier(0.22, 1, 0.36, 1), padding 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }
        .sidebar-link .fa-icon {
            transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1), color 200ms ease;
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
        .sidebar-link.active .fa-icon {
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
        .gradient-text { background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
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

        @media (hover: hover) and (pointer: fine) {
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
        $safeNotificationUrl = function (?string $url) use ($isStaff) {
            $value = (string) ($url ?? '#');
            if ($value === '' || $value === '#' || $value === url('/logout') || $value === route('logout')) {
                return '#';
            }

            if ($isStaff) {
                return $value;
            }

            foreach (['/rooms/approvals', '/rooms/manage', '/reports', '/settings', '/api/users/search', '/logout'] as $fragment) {
                if (str_contains($value, $fragment)) {
                    return route('dashboard');
                }
            }

            return $value;
        };
        $initials = $currentUser
            ? collect(preg_split('/\s+/', trim($currentUser->name)))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('')
            : 'U';
    @endphp
    <div x-data="{
        sidebarOpen: false,
        canHoverSidebar: false,
        sidebarHoverExpand: false,
        sidebarExpandTimer: null,
        sidebarCollapseTimer: null,
        syncSidebarMode() {
            const hoverCapable = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
            const hasChanged = this.canHoverSidebar !== hoverCapable;

            this.canHoverSidebar = hoverCapable;

            if (this.canHoverSidebar) {
                this.sidebarOpen = false;
            }

            if (!this.canHoverSidebar) {
                this.sidebarHoverExpand = false;
            }

            if (hasChanged) {
                window.dispatchEvent(new CustomEvent('layout:sidebar-toggled'));
            }
        },
        initSidebarMode() {
            this.syncSidebarMode();
            window.addEventListener('resize', () => this.syncSidebarMode());
        },
        handleSidebarMouseEnter() {
            if (!this.canHoverSidebar) {
                return;
            }

            if (this.sidebarCollapseTimer) {
                window.clearTimeout(this.sidebarCollapseTimer);
                this.sidebarCollapseTimer = null;
            }

            if (this.sidebarHoverExpand) {
                return;
            }

            this.sidebarExpandTimer = window.setTimeout(() => {
                this.sidebarHoverExpand = true;
                window.dispatchEvent(new CustomEvent('layout:sidebar-toggled'));
            }, 70);
        },
        handleSidebarMouseLeave() {
            if (!this.canHoverSidebar) {
                return;
            }

            if (this.sidebarExpandTimer) {
                window.clearTimeout(this.sidebarExpandTimer);
                this.sidebarExpandTimer = null;
            }

            if (!this.sidebarHoverExpand) {
                return;
            }

            this.sidebarCollapseTimer = window.setTimeout(() => {
                this.sidebarHoverExpand = false;
                window.dispatchEvent(new CustomEvent('layout:sidebar-toggled'));
            }, 120);
        },
    }" x-init="initSidebarMode()" class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="sidebar-shell fixed inset-y-0 left-0 z-50 bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 transform shadow-xl"
               @mouseenter="handleSidebarMouseEnter()"
               @mouseleave="handleSidebarMouseLeave()"
               :class="canHoverSidebar && !sidebarHoverExpand ? 'sidebar-collapsed' : ''"
               :style="canHoverSidebar
                   ? { width: sidebarHoverExpand ? '16rem' : '5rem', transform: 'translateX(0)' }
                   : (sidebarOpen
                       ? { width: '16rem', transform: 'translateX(0)' }
                       : { width: '16rem', transform: 'translateX(-100%)' })">
            <!-- Logo -->
            <div class="sidebar-header flex items-center justify-between h-20 px-4 border-b border-white/10 bg-gradient-to-r from-indigo-900/50 to-transparent">
                <div class="flex items-center gap-3">
                    <div class="relative h-12 w-12 flex items-center justify-center">
                        <span class="absolute inset-0 rounded-full bg-indigo-400/25 blur-md"></span>
                        <img src="{{ asset('images/smartspace-mark.svg') }}" alt="SmartSpace" class="relative h-12 w-12 object-contain drop-shadow-[0_0_20px_rgba(129,140,248,0.55)]" onerror="this.onerror=null;this.src='{{ asset('images/smartspace-logo.svg') }}';">
                    </div>
                    <div class="sidebar-brand">
                        <span class="text-white font-bold text-lg tracking-tight">SmartSpace</span>
                        <p class="text-indigo-300 text-xs">Reservation System</p>
                    </div>
                </div>
                <button @click="sidebarOpen = false" x-show="!canHoverSidebar" x-cloak class="text-white hover:bg-white/10 p-1.5 rounded-lg transition-colors">
                    <i class="w-5 h-5 fa-icon fa-solid fa-xmark text-xl leading-none"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="mt-6 px-3 space-y-1">
                <p class="sidebar-section-label px-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-3">Main Menu</p>
                <a href="{{ route('dashboard') }}" 
                   title="Dashboard"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="w-5 h-5 fa-icon fa-solid fa-house text-xl leading-none"></i>
                    <span class="sidebar-text">Dashboard</span>
                </a>

                @if($isStaff)
                    <a href="{{ route('rooms.index') }}" 
                       title="Manage Rooms"
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('rooms.*') ? 'active' : '' }}">
                        <i class="w-5 h-5 fa-icon fa-solid fa-building text-xl leading-none"></i>
                        <span class="sidebar-text">Manage Rooms</span>
                    </a>

                    <a href="{{ route('approvals.index') }}" 
                       title="Approvals"
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('approvals.*') ? 'active' : '' }}">
                        <i class="w-5 h-5 fa-icon fa-solid fa-circle-check text-xl leading-none"></i>
                        @php $pendingCount = \App\Models\Booking::where('status', 'pending')->count(); @endphp
                        <span class="sidebar-text">Approvals</span>
                        @if($pendingCount > 0)
                        <span class="sidebar-badge sidebar-badge-expanded ml-auto bg-gradient-to-r from-red-500 to-rose-500 text-white text-xs font-bold px-2.5 py-1 rounded-full shadow-lg shadow-red-500/30 animate-pulse" aria-label="{{ $pendingCount }} pending approvals">{{ $pendingCount }}</span>
                        <span class="sidebar-badge-collapsed inline-flex items-center justify-center min-w-[1.15rem] h-[1.15rem] px-1 rounded-full bg-rose-500 text-white text-[10px] font-bold leading-none border border-rose-300/70 shadow-lg shadow-rose-500/40" aria-hidden="true">{{ $pendingCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('reports.index') }}"
                       title="Reports"
                       class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                        <i class="w-5 h-5 fa-icon fa-solid fa-chart-column text-xl leading-none"></i>
                        <span class="sidebar-text">Reports</span>
                    </a>
                @endif

                <a href="{{ route('reservations.index') }}" 
                   title="Reservations"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('reservations.*') ? 'active' : '' }}">
                    <i class="w-5 h-5 fa-icon fa-solid fa-file text-xl leading-none"></i>
                    <span class="sidebar-text">Reservations</span>
                </a>

                <a href="{{ route('calendar.index') }}" 
                   title="Calendar"
                   class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-indigo-200 hover:text-white transition-all duration-200 mb-1 {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                    <i class="w-5 h-5 fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                    <span class="sidebar-text">Calendar</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
           <div class="content-shell flex-1 min-w-0"
               :style="canHoverSidebar ? { marginLeft: sidebarHoverExpand ? '16rem' : '5rem' } : {}">
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
                <div class="flex items-center justify-between h-20 px-4 sm:px-6">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true" x-show="!canHoverSidebar" x-cloak class="text-gray-600 hover:text-gray-900">
                            <i class="w-6 h-6 fa-icon fa-solid fa-bars text-2xl leading-none"></i>
                        </button>
                        <!-- Breadcrumb -->
                        <nav class="hidden sm:flex items-center gap-2 text-sm">
                            <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">
                                <i class="w-4 h-4 fa-icon fa-solid fa-house text-base leading-none"></i>
                            </a>
                            @yield('breadcrumb')
                        </nav>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Notifications Dropdown -->
                        <div x-data="{ notifOpen: false }"
                             id="header-notification-root"
                             data-user-id="{{ $currentUser?->id }}"
                             data-is-staff="{{ $isStaff ? '1' : '0' }}"
                             data-unread-url="{{ route('notifications.unread') }}"
                             data-approvals-url="{{ $isStaff ? route('approvals.index') : route('dashboard') }}"
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
                                        <a href="{{ $safeNotificationUrl($notification->data['url'] ?? '#') }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors">
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
                                <i class="w-4 h-4 text-gray-400 hidden sm:block fa-icon fa-solid fa-chevron-down text-base leading-none"></i>
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
                                    <i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-user text-base leading-none"></i>
                                    My Profile
                                </a>
                                @if($isStaff)
                                    <a href="{{ route('settings.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                        <i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-gear text-base leading-none"></i>
                                        Settings
                                    </a>
                                @endif
                                <hr class="my-2">
                                <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="px-2">
                                    @csrf
                                    <button type="button" class="w-full flex items-center gap-3 px-2 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors rounded-lg"
                                        @click="logoutOpen = true">
                                        <i class="w-4 h-4 fa-icon fa-solid fa-right-from-bracket text-base leading-none"></i>
                                        Sign Out
                                    </button>
                                </form>
                            </div>

                            <!-- Logout Confirmation Modal -->
                            <div x-show="logoutOpen" x-cloak class="modal p-4" :class="{ 'modal-open': logoutOpen }" @keydown.escape.window="logoutOpen = false">
                                <div class="modal-box w-11/12 max-w-md p-0 bg-transparent border-0 shadow-none overflow-visible" @click.stop>
                                    <div class="relative group">
                                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-10 -bottom-10 h-16 bg-gradient-to-r from-indigo-500 via-purple-500 to-teal-500 blur-3xl opacity-30"></div>
                                        <div class="bg-gradient-to-b from-white to-slate-50 rounded-3xl border border-gray-200 shadow-2xl max-h-[88vh] overflow-hidden flex flex-col">
                                    <div class="px-6 py-5 border-b border-gray-100 bg-white/60 flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">Logout</h3>
                                            <p class="text-sm text-gray-500 mt-1">Are you sure you want to logout?</p>
                                        </div>
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
                                <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="logoutOpen = false">close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 min-w-0">
                @if(session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 shadow-sm">
                        {{ session('status') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>

        <!-- Overlay for mobile sidebar -->
         <div x-show="sidebarOpen && !canHoverSidebar" @click="sidebarOpen = false" x-cloak
             class="fixed inset-0 bg-black/30 backdrop-blur-sm z-40"></div>
    </div>

    <div id="swUpdateToast" class="hidden fixed left-1/2 bottom-6 z-50 w-[92%] max-w-md -translate-x-1/2 rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm shadow-xl">
        <div class="flex items-center justify-between gap-3">
            <p class="text-slate-700">A new SmartSpace version is ready.</p>
            <button id="swUpdateBtn" type="button" class="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                Refresh
            </button>
        </div>
    </div>

    <script>
    if ('serviceWorker' in navigator) {
        let swRegistration;
        let refreshing = false;
        const updateToast = document.getElementById('swUpdateToast');
        const updateBtn = document.getElementById('swUpdateBtn');

        const showUpdateToast = function () {
            if (updateToast) {
                updateToast.classList.remove('hidden');
            }
        };

        const bindUpdateFlow = function (registration) {
            if (!registration) {
                return;
            }

            if (registration.waiting) {
                showUpdateToast();
            }

            registration.addEventListener('updatefound', function () {
                const newWorker = registration.installing;
                if (!newWorker) {
                    return;
                }

                newWorker.addEventListener('statechange', function () {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdateToast();
                    }
                });
            });
        };

        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (refreshing) {
                return;
            }
            refreshing = true;
            window.location.reload();
        });

        updateBtn?.addEventListener('click', function () {
            if (swRegistration && swRegistration.waiting) {
                swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        });

        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function (registration) {
                    swRegistration = registration;
                    bindUpdateFlow(registration);
                    window.setInterval(function () {
                        registration.update();
                    }, 60 * 60 * 1000);
                })
                .catch(function(error) {
                    console.error('Service worker registration failed', error);
                });
        });
    }
    </script>

    @stack('scripts')
</body>
</html>
