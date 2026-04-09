@extends('layouts.app')

@section('title', 'Dashboard | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Dashboard</span>
@endsection

@section('content')
@php
    $audienceLabel = ucfirst($dashboardAudience ?? 'admin');
@endphp
<div x-data="dashboardApp()" x-init="init()" class="flex flex-col">
    <!-- Main Dashboard Body -->
    <div class="flex-1 min-h-0 overflow-y-auto px-1 group/dashboard">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-8">
            
            <!-- Left Column: Welcome, Stats, Quick Actions (lg:col-span-4) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Welcome Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl border border-indigo-400/20 shadow-lg p-6 relative overflow-hidden group/welcome hover:shadow-xl transition-all duration-300 animate-slide-in-up stagger-1">
                    <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/welcome:scale-110 transition-transform">
                        <i class="fa-solid fa-lightbulb text-9xl text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-bold text-white">Welcome, Admin!</h2>
                        <div class="mt-2 inline-flex items-center rounded-full border border-white/30 bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-white">{{ $audienceLabel }}</div>
                        <p class="text-indigo-100 mt-2 text-sm leading-relaxed">Modernizing your library experience. Manage room operations, approvals, and dashboard analytics from one place.</p>
                        
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ route('approvals.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-indigo-700 text-xs font-bold rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md active:translate-y-0">
                                <i class="fa-solid fa-calendar opacity-70"></i> Manage Bookings
                            </a>
                            <a href="{{ route('reservations.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-800/50 hover:bg-indigo-800/70 text-white text-xs font-semibold rounded-xl transition-all border border-indigo-500/30 hover:shadow-md">
                                <i class="fa-solid fa-list opacity-70"></i> My Reservations
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Grid -->
                <div class="grid grid-cols-2 gap-3 animate-slide-in-up stagger-2">
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-all duration-300 group/stat hover:-translate-y-0.5 cursor-pointer"
                         @click="openStatsModal('Pending Bookings', {{ json_encode($pendingBookingsList) }})">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center group-hover/stat:scale-110 transition-transform">
                                <i class="fa-solid fa-clock text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $stats['pending'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-all duration-300 group/stat hover:-translate-y-0.5 cursor-pointer"
                         @click="openStatsModal('Approved Bookings', {{ json_encode($approvedBookingsList) }})">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center group-hover/stat:scale-110 transition-transform">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $stats['approved'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Approved</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-all duration-300 group/stat hover:-translate-y-0.5 cursor-pointer"
                         @click="openStatsModal('Rejected Bookings', {{ json_encode($rejectedBookingsList) }})">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-100 to-rose-100 flex items-center justify-center group-hover/stat:scale-110 transition-transform">
                                <i class="fa-solid fa-circle-xmark text-red-500"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $stats['rejected'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Rejected</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-md transition-all duration-300 group/stat hover:-translate-y-0.5 cursor-pointer"
                         @click="openStatsModal('Today\'s Bookings', {{ json_encode($todayBookingsList) }})">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center group-hover/stat:scale-110 transition-transform">
                                <i class="fa-solid fa-calendar-day text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $stats['today'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Today</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collaborative Rooms Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-3">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-door-open text-emerald-600"></i>
                           Collaborative Rooms
                        </h3>
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($collaborativeRooms as $room)
                                @php
                                    if ($room->isUnderMaintenanceForDashboard()) {
                                        $statusLabel = 'Under Maintenance';
                                        $containerClasses = 'bg-yellow-50 border-yellow-200 text-amber-800';
                                        $badgeClasses = 'bg-amber-100 text-amber-700';
                                    } elseif ($room->isOccupiedForDashboard()) {
                                        $statusLabel = 'Occupied';
                                        $containerClasses = 'bg-red-50 border-red-200 text-red-800';
                                        $badgeClasses = 'bg-red-100 text-red-700';
                                    } else {
                                        $statusLabel = 'Available';
                                        $containerClasses = 'bg-green-50 border-green-200 text-emerald-800';
                                        $badgeClasses = 'bg-emerald-100 text-emerald-700';
                                    }
                                @endphp
                                <div class="rounded-3xl border p-4 flex items-center justify-between {{ $containerClasses }}" data-collab-room-id="{{ $room->id }}">
                                    <div>
                                        <p class="text-sm font-semibold">{{ $room->name }}</p>
                                    </div>
                                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $badgeClasses }}"
                                          data-collab-room-status="{{ $statusLabel }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals Queue -->
                @if($pendingBookings->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-4">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-inbox text-amber-500"></i>
                           Pending Approvals
                           <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-100 text-amber-700">{{ $stats['pending'] }}</span>
                        </h3>
                        <a href="{{ route('approvals.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 transition-colors">
                            View All &rarr;
                        </a>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-[320px] overflow-y-auto">
                        @foreach($pendingBookings->take(5) as $booking)
                        <div class="flex items-center gap-4 p-4 hover:bg-amber-50/30 transition-colors cursor-pointer group/pending"
                             @click="viewBooking({{ json_encode($booking) }})">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 group-hover/pending:bg-amber-200 transition-colors">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-semibold text-gray-900 truncate">{{ $booking->room->name ?? 'Room' }}</h4>
                                <p class="text-xs text-gray-500 truncate">{{ $booking->user_name }} &bull; {{ $booking->date->format('M j') }} &bull; {{ $booking->formatted_time }}</p>
                            </div>
                            <span class="shrink-0 px-2 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 uppercase">Pending</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Room Utilization -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-5">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-chart-bar text-indigo-500"></i>
                           Room Utilization
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        @foreach($rooms as $room)
                        @php
                            $roomBookings = $collabRoomBookings->where('room_id', $room->id)->values();
                            $todayBookingsForRoom = $roomBookings->filter(fn($b) => $b->date->isToday())->values();
                            $upcomingBookingsForRoom = $roomBookings->filter(fn($b) => !$b->date->isToday() && $b->date->isAfter(today()))->values();
                            $roomBookingCount = $roomBookings->count();
                            $utilPercent = min(100, $roomBookingCount * 10);
                        @endphp
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl hover:bg-indigo-50 hover:shadow-sm hover:-translate-y-0.5 transition-all duration-300 cursor-pointer border border-transparent hover:border-indigo-100 group/room"
                             @click="openRoomModal({{ json_encode($room) }}, {{ $roomBookingCount }}, {{ json_encode($todayBookingsForRoom) }}, {{ json_encode($upcomingBookingsForRoom) }})">
                            <div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center shrink-0 border border-gray-100 group-hover/room:border-indigo-200 group-hover/room:bg-indigo-600 transition-colors">
                                <i class="fa-solid fa-door-open text-gray-400 group-hover/room:text-white transition-colors"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1.5">
                                    <span class="text-xs font-bold text-gray-900 truncate group-hover/room:text-indigo-700 transition-colors">{{ $room->name }}</span>
                                    <span class="text-[10px] font-black uppercase text-indigo-600 tracking-wider bg-indigo-50 px-2 py-0.5 rounded-md">{{ $roomBookingCount }} bookings</span>
                                </div>
                                <div class="h-2 bg-gray-200/80 rounded-full overflow-hidden shadow-inner">
                                    <div class="h-full bg-gradient-to-r from-indigo-500 via-purple-500 to-indigo-500 bg-[length:200%_auto] animate-gradient rounded-full transition-all duration-700 group-hover/room:shadow-[0_0_10px_rgba(99,102,241,0.5)]" style="width: {{ $utilPercent }}%"></div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Today's QR Codes Quick Access -->
                @php
                    $todayBookings = $collabRoomBookings->filter(fn($b) => $b->date->isToday() && $b->status === 'approved')->values();
                @endphp
                @if($todayBookings->count() > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-6">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gradient-to-r from-indigo-50 to-purple-50">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-qrcode text-indigo-600"></i>
                           Today's QR Codes
                           <span class="px-2 py-0.5 text-[10px] font-black rounded-full bg-indigo-100 text-indigo-700">{{ $todayBookings->count() }}</span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">Quick access to today's booking QR codes</p>
                    </div>
                    <div class="p-3 space-y-2 max-h-[320px] overflow-y-auto">
                        @foreach($todayBookings as $qrBooking)
                        <div class="flex items-center gap-3 p-3 rounded-xl border border-gray-50 hover:border-indigo-200 hover:bg-indigo-50/30 transition-all cursor-pointer group/qr"
                             @click="viewBooking({{ json_encode($qrBooking) }})">
                            @if($qrBooking->qr_code_url)
                            <div class="w-12 h-12 rounded-xl bg-white border border-gray-100 shadow-sm flex items-center justify-center shrink-0 overflow-hidden group-hover/qr:border-indigo-300 transition-colors">
                                <img src="{{ $qrBooking->qr_code_url }}" alt="QR" class="w-10 h-10 object-contain">
                            </div>
                            @else
                            <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center shrink-0 group-hover/qr:border-indigo-300 transition-colors">
                                <i class="fa-solid fa-qrcode text-gray-300"></i>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-gray-900 truncate group-hover/qr:text-indigo-700 transition-colors">{{ $qrBooking->room->name }}</h4>
                                <p class="text-xs text-gray-500">{{ $qrBooking->formatted_time }} &bull; {{ $qrBooking->user_name }}</p>
                            </div>
                            <i class="fa-solid fa-chevron-right text-gray-300 group-hover/qr:text-indigo-400 text-xs transition-colors"></i>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            <div id="admin-calendar-section" class="lg:col-span-8 space-y-6">
                <!-- Mini Calendar Subsection -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-2">
                    <div class="bg-gradient-to-r from-purple-700 to-blue-600 px-6 py-4 grid grid-cols-1 sm:grid-cols-3 items-center gap-4 relative overflow-hidden">
                        <div class="absolute -right-2 -bottom-4 opacity-10 transform -rotate-12 pointer-events-none">
                            <i class="fa-solid fa-calendar-days text-7xl text-white"></i>
                        </div>
                        
                        <!-- Left: Main Title -->
                        <div class="relative z-10 flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-md shadow-sm shrink-0">
                                <i class="fa-solid fa-calendar text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-white text-lg tracking-tight leading-tight">Availability Calendar</h3>
                                <p class="text-[10px] text-purple-100 uppercase tracking-widest font-semibold sm:hidden" x-text="calendarTitle"></p>
                            </div>
                        </div>

                        <!-- Middle: Month & Year (Hidden on mobile) -->
                        <div class="relative z-10 hidden sm:flex justify-center text-center">
                            <h2 class="text-2xl font-black text-white tracking-widest drop-shadow-md" x-text="calendarTitle"></h2>
                        </div>

                        <!-- Right: Controls -->
                        <div class="flex items-center justify-start sm:justify-end gap-2 relative z-10">
                            <div class="flex items-center gap-1 bg-black/20 backdrop-blur-md rounded-xl p-1 border border-white/10">
                                <button @click="prevMonth()" class="p-1.5 rounded-lg text-white hover:bg-white/20 transition-all cursor-pointer"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                                <button @click="goToToday()" class="px-2 py-1 text-[10px] font-black uppercase text-purple-100 hover:text-white tracking-widest cursor-pointer">TODAY</button>
                                <button @click="nextMonth()" class="p-1.5 rounded-lg text-white hover:bg-white/20 transition-all cursor-pointer"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                            </div>
                            <div class="flex items-center gap-1 bg-black/20 backdrop-blur-md rounded-xl p-1 border border-white/10">
                                <button @click="changeDashboardView('dayGridMonth')" :class="calendarView === 'dayGridMonth' ? 'bg-white/20 shadow-sm text-white' : 'text-purple-100 hover:text-white'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer">Month</button>
                                <button @click="changeDashboardView('listWeek')" :class="calendarView === 'listWeek' ? 'bg-white/20 shadow-sm text-white' : 'text-purple-100 hover:text-white'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all cursor-pointer">List</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 transition-all duration-500 min-h-[400px]">
                        <div x-show="calendarView === 'dayGridMonth'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="border border-gray-100 rounded-2xl overflow-x-auto shadow-inner bg-gray-50/10">
                            <div class="min-w-[700px]">
                                <div class="grid grid-cols-7 bg-white/50 border-b border-gray-100">
                                    <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                                        <div class="py-3 text-center text-[10px] font-black uppercase text-gray-400 tracking-widest" x-text="day"></div>
                                    </template>
                                </div>

                                <div class="grid grid-cols-7">
                                    <template x-for="(day, index) in calCells" :key="day.date || index">
                                        <div class="min-h-[110px] sm:min-h-[130px] border-b border-r border-gray-100 p-2 relative group/day transition-all"
                                             @click="!day.isPast && day.isCurrentMonth && openBookingModalForDay(day)"
                                             :class="{
                                                 'bg-gray-50/50': !day.isCurrentMonth,
                                                 'bg-teal-50/30': day.isToday,
                                                 'cursor-pointer hover:bg-teal-50/80 hover:z-10 hover:shadow-lg': day.isCurrentMonth && !day.isPast,
                                                 'cursor-not-allowed': !day.isCurrentMonth || day.isPast,
                                                 'opacity-40 grayscale': day.isPast && day.isCurrentMonth,
                                             }">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs font-black"
                                                      :class="day.isToday ? 'bg-teal-600 text-white w-6 h-6 rounded-full flex items-center justify-center -ml-1' : (day.isCurrentMonth ? 'text-gray-600' : 'text-gray-300')"
                                                      x-text="day.day"></span>
                                            </div>
                                            <div class="space-y-1 overflow-hidden">
                                                <template x-for="event in day.events.slice(0, 3)" :key="event.id">
                                                    <div class="relative group/event">
                                                                                                                     <div class="text-[10px] px-2 py-1 bg-white border border-gray-100 text-gray-700 rounded-lg shadow-sm truncate transition-all font-medium flex items-center gap-1.5"
                                                                                                                         @click.stop="day.isCurrentMonth && !day.isPast && openViewBookingModal(event)"
                                                                                                                         :class="day.isCurrentMonth && !day.isPast ? 'cursor-pointer hover:border-teal-400 hover:text-teal-700' : 'cursor-not-allowed'">
                                                                <span class="w-1.5 h-1.5 rounded-full" :class="event.status === 'approved' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                                <span x-text="formatEventChipTime(event)"></span>
                                                                <span class="opacity-60">|</span>
                                                                <span x-text="event.room_name?.split(' ')[1] || event.room_name"></span>
                                                            </div>
                                                    </div>
                                                </template>
                                                <template x-if="day.events.length > 3">
                                                    <button @click.stop="day.isCurrentMonth && !day.isPast && openDayEventsModal(day)"
                                                            class="w-full text-[10px] text-teal-600 font-black uppercase text-center py-1 bg-teal-50/50 rounded-lg transition-all"
                                                            :class="day.isCurrentMonth && !day.isPast ? 'cursor-pointer hover:bg-teal-100' : 'cursor-not-allowed'"
                                                            x-text="'+' + (day.events.length - 3) + ' more'"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div x-show="calendarView === 'listWeek'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="h-[500px] border border-gray-100 rounded-2xl p-4 overflow-auto bg-gray-50/10">
                            <div id="dashboard-calendar" class="fc-custom-dashboard h-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Active Bookings Panel (Compact Version) -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-all duration-300 animate-slide-in-up stagger-4">
                    <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
                        Recent Room Activity
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @forelse($collabRoomBookings as $booking)
                        <div class="flex items-center gap-4 p-4 rounded-2xl border border-gray-50 hover:border-teal-100 hover:bg-teal-50/30 transition-all cursor-pointer group/item"
                             @click="viewBooking({{ json_encode($booking) }})">
                            <div class="w-12 h-12 rounded-xl bg-gray-50 group-hover/item:bg-white flex items-center justify-center shrink-0 transition-colors">
                                <i class="fa-solid fa-door-open text-gray-400 group-hover/item:text-teal-600"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4 class="font-bold text-gray-900 truncate">{{ $booking->room->name }}</h4>
                                <p class="text-xs text-gray-500">{{ $booking->formatted_time }} &bull; {{ $booking->date->format('M d') }}</p>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-black uppercase rounded-lg">
                                {{ $booking->status }}
                            </span>
                        </div>
                        @empty
                        <div class="md:col-span-2 text-center py-12 border-2 border-dashed border-gray-100 rounded-3xl">
                            <i class="fa-solid fa-calendar-xmark text-4xl text-gray-200 mb-4"></i>
                            <p class="text-gray-400 text-sm font-medium">No bookings found for the upcoming period.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Day Events Modal (for +X more) -->
    <x-modals.dashboard.day-events />

    <!-- Room Details Modal -->
    <x-modals.dashboard.room-details />

    <!-- View Booking Details Modal (Highest z-index of info modals) -->
    <x-modals.dashboard.view-booking />

    <!-- Create Booking Modal -->
    <x-modals.dashboard.create-booking :rooms="$rooms" />

    <!-- Stats Modal -->
    <x-modals.dashboard.stats-modal />
</div>

@push('styles')
<style>
/* Ensure dashboard calendar rows render in natural top-to-bottom order and do not wrap into columns */
#dashboard-calendar .fc-daygrid-body {
    display: block !important;
}
#dashboard-calendar .fc-daygrid-body .fc-row {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    order: 0 !important;
}
#dashboard-calendar .fc-daygrid-body .fc-daygrid-day {
    display: flex !important;
    flex-direction: column !important;
    min-height: 80px; /* keep cells a reasonable height */
}
</style>
@endpush

@push('scripts')
@php
    $verifiedRegistration = auth()->user()?->qcidRegistration;
    $hasVerifiedRegistration = $verifiedRegistration && $verifiedRegistration->verification_status === 'verified';

    $roomOptions = $rooms->map(function ($room) {
        return [
            'id' => $room->id,
            'capacity' => $room->standardBookingCapacityLimit(),
            'is_collaborative' => $room->isCollaborative(),
            'standard_limit' => $room->standardBookingCapacityLimit(),
            'student_limit' => $room->maxStudentBookingCapacity(),
        ];
    })->values();
@endphp
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script type="application/json" id="dashboard-calendar-config">
{!! json_encode([
    'hasVerifiedRegistration' => $hasVerifiedRegistration,
    'verifiedRegistrationName' => $verifiedRegistration?->full_name,
    'isStaffUser' => auth()->user()?->isStaff() ?? false,
    'rooms' => $roomOptions,
    'defaultDate' => now()->format('Y-m-d'),
    'initialCalendarData' => $calendarData,
    'monthDataUrl' => route('calendar.month'),
    'eventsUrl' => route('calendar.events'),
    'availabilityUrl' => route('calendar.availability'),
    'verifyQcIdUrl' => route('qcid.verify'),
    'storeBookingUrl' => route('reservations.store'),
]) !!}
</script>
<script>
window.dashboardCalendarConfig = JSON.parse(document.getElementById('dashboard-calendar-config').textContent);
</script>
@endpush
@endsection
