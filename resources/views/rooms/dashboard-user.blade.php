@extends('layouts.app')

@section('title', 'My Dashboard | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Dashboard</span>
@endsection

@section('content')
@php
    $currentUser = auth()->user();
    $audience = $dashboardAudience ?? 'student';
    $audienceLabel = ucfirst($audience);
    $audienceDescription = $audience === 'faculty'
        ? 'Faculty dashboard for room scheduling, reservation tracking, and streamlined approvals follow-up.'
        : 'Student dashboard for managing reservations, reviewing schedules, and planning study sessions.';

    $fullName = trim((string) ($currentUser?->name ?? 'User'));
    if (str_contains($fullName, ',')) {
        $welcomeSurname = trim((string) strtok($fullName, ','));
    } else {
        $nameParts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $welcomeSurname = ! empty($nameParts) ? trim((string) end($nameParts)) : $fullName;
    }

    $sexValue = strtoupper(trim((string) ($qcIdRegistration?->sex ?? '')));
    $honorific = '';
    if (($classification ?? null) === \App\Models\User::CLASSIFICATION_FACULTY) {
        if (in_array($sexValue, ['M', 'MALE'], true)) {
            $honorific = 'Sir ';
        } elseif (in_array($sexValue, ['F', 'FEMALE'], true)) {
            $honorific = "Ma'am ";
        }
    }

    $welcomeDisplayName = trim($honorific . $welcomeSurname);
@endphp
<div x-data="dashboardApp()" x-init="init()" class="flex flex-col xl:h-[calc(100dvh-9rem)] xl:overflow-hidden">
    <div class="flex-1 min-h-0 overflow-y-auto px-1 group/dashboard">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-8">

            <!-- Left Column: Welcome, Stats, Verification (lg:col-span-4) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Welcome Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl border border-indigo-500/20 shadow-lg p-6 relative overflow-hidden group/welcome hover:shadow-xl transition-all duration-300 animate-slide-in-up stagger-1">
                    <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/welcome:scale-110 transition-transform">
                        <i class="fa-solid fa-book-open-reader text-9xl text-white"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-bold text-white">Welcome, {{ $welcomeDisplayName }}!</h2>
                        <div class="mt-2 inline-flex items-center rounded-full border border-white/30 bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em] text-white">{{ $audienceLabel }}</div>
                        <p class="text-indigo-100 mt-2 text-sm leading-relaxed">{{ $audienceDescription }}</p>

                        <div class="mt-5 mb-6 p-4 rounded-xl bg-white/10 border border-white/20 backdrop-blur-sm group-hover/welcome:bg-white/15 transition-all">
                            <h3 class="font-bold text-white text-xs uppercase tracking-wider mb-2 opacity-90 flex items-center gap-2">
                                <i class="fa-solid fa-lightbulb text-indigo-200"></i> Quick Tips
                            </h3>
                            <ul class="space-y-1.5 text-indigo-100 text-xs leading-relaxed">
                                <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 opacity-80"></i> Book up to 14 days ahead</li>
                                <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 opacity-80"></i> Approved bookings generate a QR code for seamless check-in</li>
                                <li class="flex items-start gap-2"><i class="fa-solid fa-check mt-0.5 opacity-80"></i> Cancellations must be made at least 2 hours before your scheduled slot</li>
                            </ul>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button @click="openBookingModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-white text-indigo-700 text-xs font-bold rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md active:translate-y-0">
                                <i class="fa-solid fa-plus opacity-70"></i> New Booking
                            </button>
                            <a href="{{ route('reservations.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-800/40 hover:bg-indigo-800/60 text-white text-xs font-semibold rounded-xl transition-all border border-indigo-400/30 hover:shadow-md">
                                <i class="fa-solid fa-list opacity-70"></i> My Reservations
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Cards -->
                <div class="grid grid-cols-2 gap-3">
                    <div @click="openStatsModal('Total Bookings', window.dashboardCalendarConfig.userStatsBookings.total)" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center">
                                <i class="fa-solid fa-calendar-check text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $userStats['total'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Total</p>
                            </div>
                        </div>
                    </div>
                    <div @click="openStatsModal('Upcoming Bookings', window.dashboardCalendarConfig.userStatsBookings.upcoming)" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center">
                                <i class="fa-solid fa-rocket text-emerald-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $userStats['upcoming'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Upcoming</p>
                            </div>
                        </div>
                    </div>
                    <div @click="openStatsModal('Pending Bookings', window.dashboardCalendarConfig.userStatsBookings.pending)" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center">
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $userStats['pending'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Pending</p>
                            </div>
                        </div>
                    </div>
                    <div @click="openStatsModal('Approved Bookings', window.dashboardCalendarConfig.userStatsBookings.approved)" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 hover:shadow-[0_0_15px_rgba(99,102,241,0.4)] transition-all duration-300 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-teal-100 to-cyan-100 flex items-center justify-center">
                                <i class="fa-solid fa-circle-check text-teal-600"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-black text-gray-900">{{ $userStats['approved'] }}</p>
                                <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wide">Approved</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Verification Status Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-shield-check text-teal-600"></i>
                           QC ID Status
                        </h3>
                        @if($isVerified)
                        <span class="flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 rounded-full px-3 py-1">
                            <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Verified
                        </span>
                        @else
                        <span class="flex items-center gap-1.5 text-xs font-bold text-amber-700 bg-amber-50 rounded-full px-3 py-1">
                            <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
                            Pending
                        </span>
                        @endif
                    </div>
                    <div class="p-6">
                        @if($isVerified && $qcIdRegistration)
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl">
                                <dt class="text-emerald-800 font-semibold text-xs uppercase">Full Name</dt>
                                <dd class="font-bold text-emerald-700">{{ $qcIdRegistration->full_name }}</dd>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl">
                                <dt class="text-blue-800 font-semibold text-xs uppercase">QC ID Number</dt>
                                <dd class="font-bold text-blue-700 font-mono">{{ $qcIdRegistration->qcid_number ?? '-' }}</dd>
                            </div>
                        </dl>
                        @else
                        <div class="text-center py-4">
                            <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-id-card text-amber-600 text-2xl"></i>
                            </div>
                            <p class="text-sm font-semibold text-gray-900">QC ID Not Yet Verified</p>
                            <p class="text-xs text-gray-500 mt-1">Your QC ID will be verified during your first booking.</p>
                        </div>
                        @endif
                    </div>
                </div>



                <!-- Upcoming Bookings -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            <i class="fa-solid fa-bolt text-amber-500"></i>
                            Upcoming Bookings
                        </h3>
                        <a href="{{ route('reservations.index') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 transition-colors">
                            View All &rarr;
                        </a>
                    </div>
                    <div class="p-4">
                        @if($upcomingBookings->count() > 0)
                        <div class="space-y-3">
                            @foreach($upcomingBookings as $booking)
                            <div class="flex items-center gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-white hover:shadow-sm transition-all duration-200 group/booking cursor-pointer"
                                 @click="viewBooking({{ json_encode($booking) }})">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-teal-100 to-cyan-100 flex flex-col items-center justify-center shrink-0">
                                    <span class="text-[10px] font-bold text-teal-600 uppercase">{{ $booking->date->format('M') }}</span>
                                    <span class="text-lg font-black text-teal-700 leading-none">{{ $booking->date->format('j') }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-semibold text-gray-900 text-sm group-hover/booking:text-teal-600 transition-colors">{{ $booking->room->name }}</h4>
                                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                                        <span class="flex items-center gap-1">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ $booking->time ?? ($booking->start_time . ' - ' . $booking->end_time) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <i class="fa-solid fa-users"></i>
                                            {{ $booking->attendees ?? 0 }}
                                        </span>
                                    </div>
                                </div>
                                <span class="shrink-0 px-3 py-1 rounded-full text-xs font-semibold
                                    @if($booking->status === 'approved') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @elseif($booking->status === 'pending') bg-amber-50 text-amber-700 border border-amber-200
                                    @else bg-red-50 text-red-700 border border-red-200 @endif">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-10">
                            <div class="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-4">
                                <i class="fa-solid fa-calendar-xmark text-gray-400 text-2xl"></i>
                            </div>
                            <h4 class="font-semibold text-gray-900">No Upcoming Bookings</h4>
                            <p class="text-sm text-gray-500 mt-1">Create a new booking to get started!</p>
                            <button @click="openBookingModal()" class="mt-4 inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-xl transition-all hover:scale-[1.02]">
                                <i class="fa-solid fa-plus"></i> Create Booking
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Column: Calendar & Bookings (lg:col-span-8) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Calendar -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-all duration-300">
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
                                                                                                                         :class="day.isCurrentMonth && !day.isPast
                                                                                                                            ? (event.is_owner
                                                                                                                                ? 'cursor-pointer hover:border-emerald-400 hover:text-emerald-700'
                                                                                                                                : 'cursor-pointer hover:border-rose-400 hover:text-rose-700')
                                                                                                                            : 'cursor-not-allowed'">
                                                                <span class="w-1.5 h-1.5 rounded-full" :class="event.is_owner ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                                                <span x-text="formatEventChipTime(event)"></span>
                                                                <span class="opacity-60">|</span>
                                                                <span x-text="event.room_name?.split(' ')[1] || event.room_name"></span>
                                                            </div>
                                                    </div>
                                                </template>
                                                <template x-if="day.events.length > 3">
                                                    <button @click.stop="day.isCurrentMonth && !day.isPast && openDayEventsModal(day)"
                                                        class="text-[9px] w-full text-left font-bold text-teal-600 px-1 py-0.5 rounded transition-colors"
                                                        :class="day.isCurrentMonth && !day.isPast ? 'cursor-pointer hover:text-teal-800 hover:underline' : 'cursor-not-allowed'"
                                                        x-text="'+ ' + (day.events.length - 3) + ' more'"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div x-show="calendarView === 'listWeek'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-3">
                            <template x-for="(event, idx) in listEvents" :key="idx">
                                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4 p-4 rounded-xl border border-gray-100 bg-gray-50/40 hover:bg-white hover:shadow-sm hover:-translate-y-0.5 transition-all cursor-pointer group"
                                     @click="openViewBookingModal(event)">
                                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                                        <div class="text-center shrink-0 w-12 h-12 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col items-center justify-center group-hover:border-teal-300 group-hover:bg-teal-50 transition-colors">
                                            <p class="text-[9px] font-bold text-teal-600 uppercase" x-text="new Date(event.date + 'T00:00:00').toLocaleDateString('en-US', {weekday: 'short'})"></p>
                                            <p class="text-lg font-black text-gray-900 group-hover:text-teal-700" x-text="new Date(event.date + 'T00:00:00').getDate()"></p>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-900 group-hover:text-teal-700 transition-colors truncate" x-text="event.room_name"></p>
                                            <div class="flex flex-col sm:flex-row sm:items-center gap-0.5 sm:gap-2 mt-0.5 text-xs text-gray-500">
                                                <span class="flex items-center gap-1 font-medium truncate"><i class="fa-regular fa-clock"></i> <span x-text="event.formatted_time || ''"></span></span>
                                                <span class="hidden sm:inline opacity-50">&bull;</span>
                                                <span class="flex items-center gap-1 truncate"><i class="fa-solid fa-user"></i> <span x-text="event.user_name || ''"></span></span>
                                            </div>
                                        </div>
                                    </div>
                                                                        <span class="self-start sm:self-center shrink-0 px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-wider transition-colors border ml-15 sm:ml-0"
                                                                                    :class="event.is_owner
                                                                                        ? 'bg-emerald-100 text-emerald-700 border-emerald-200 group-hover:bg-emerald-600 group-hover:text-white group-hover:border-emerald-600'
                                                                                        : 'bg-rose-100 text-rose-700 border-rose-200 group-hover:bg-rose-600 group-hover:text-white group-hover:border-rose-600'"
                                                                                    x-text="event.is_owner ? 'My Booking' : 'Occupied'"></span>
                                </div>
                            </template>
                            <template x-if="listEvents.length === 0">
                                <div class="text-center py-12 bg-gray-50/50 rounded-2xl border border-dashed border-gray-200">
                                    <div class="w-16 h-16 bg-white rounded-full shadow-sm border border-gray-100 flex items-center justify-center mx-auto mb-3">
                                        <i class="fa-solid fa-calendar-check text-gray-300 text-2xl"></i>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-900">No appointments scheduled</p>
                                    <p class="text-xs text-gray-500 mt-1">There are no upcoming bookings for this week.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Recent Booking History -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                        <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                            Recent Activity
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-50">
                        @forelse($userBookings->take(5) as $booking)
                        <div class="flex items-center gap-4 p-4 hover:bg-gray-50/50 transition-colors cursor-pointer group/recent"
                             @click="viewBooking({{ json_encode($booking) }})">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                                @if($booking->status === 'approved') bg-emerald-100
                                @elseif($booking->status === 'pending') bg-amber-100
                                @else bg-red-100 @endif">
                                @if($booking->status === 'approved')
                                <i class="fa-solid fa-check text-emerald-600"></i>
                                @elseif($booking->status === 'pending')
                                <i class="fa-solid fa-hourglass-half text-amber-600"></i>
                                @else
                                <i class="fa-solid fa-xmark text-red-600"></i>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">{{ $booking->room->name ?? 'Room' }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $booking->date->format('M j, Y') }}
                                    @if($booking->time) &bull; {{ $booking->time }} @endif
                                </p>
                            </div>
                            <span class="text-[10px] font-bold uppercase tracking-wide
                                @if($booking->status === 'approved') text-emerald-600
                                @elseif($booking->status === 'pending') text-amber-600
                                @else text-red-600 @endif">
                                {{ $booking->status }}
                            </span>
                        </div>
                        @empty
                        <div class="p-8 text-center">
                            <p class="text-sm text-gray-500">No booking history yet.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Modal -->
    <x-modals.dashboarduser.view-booking />

    <!-- Day Events Modal -->
    <x-modals.dashboarduser.day-events />

    <!-- Stats Modal -->
    <x-modals.dashboarduser.stats />

    <!-- Create Booking Modal -->
    <x-modals.dashboarduser.create-booking :rooms="$rooms" />

</div>

@push('styles')
<style>
#dashboard-calendar .fc-daygrid-body { display: block !important; }
#dashboard-calendar .fc-daygrid-body .fc-row { display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; order: 0 !important; }
#dashboard-calendar .fc-daygrid-body .fc-daygrid-day { display: flex !important; flex-direction: column !important; min-height: 80px; }
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
    'userName' => $verifiedRegistration?->full_name ?? auth()->user()?->name ?? '',
    'userEmail' => auth()->user()?->email ?? '',
    'verifiedQcIdNumber' => $verifiedRegistration?->qcid_number ?? '',
    'verifiedRegistrationValidUntil' => optional($verifiedRegistration?->valid_until)->format('Y-m-d') ?? '',
    'isStaffUser' => auth()->user()?->isStaff() ?? false,
    'userStatsBookings' => $userStatsBookings ?? [],
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
