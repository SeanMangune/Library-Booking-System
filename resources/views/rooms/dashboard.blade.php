@extends('layouts.app')

@section('title', 'Dashboard - Library Booking System')

@section('breadcrumb')
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-700 font-medium">Rooms</span>
@endsection

@section('content')
<div x-data="dashboardApp()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rooms Overview</h1>
            <p class="text-sm text-gray-500 mt-1">View Details for Scheduled Rooms Today, Upcoming Reservations, Calendar</p>
        </div>
        <button @click="openBookingModal()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Create Booking
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Today's & Upcoming Reservations -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Today's Reservations -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Today's Reservations</h2>
                </div>
                <div class="p-4 max-h-80 overflow-y-auto">
                    @forelse($todayReservations as $booking)
                    <div class="mb-3 last:mb-0 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                         @click="viewBooking({{ json_encode([
                             'id' => $booking->id,
                             'title' => $booking->title,
                             'room_name' => $booking->room->name,
                             'date' => $booking->date->format('M d, Y'),
                             'formatted_time' => $booking->formatted_time,
                             'user_name' => $booking->user_name,
                             'attendees' => $booking->attendees,
                             'status' => $booking->status,
                         ]) }})">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $booking->formatted_time }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $booking->date->format('F d, Y') }}</p>
                            </div>
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500">No reservations for today</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Upcoming Reservations -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Upcoming Reservations</h2>
                </div>
                <div class="p-4 max-h-96 overflow-y-auto">
                    @forelse($upcomingReservations as $booking)
                    <div class="mb-3 last:mb-0 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                         @click="viewBooking({{ json_encode([
                             'id' => $booking->id,
                             'title' => $booking->title,
                             'room_name' => $booking->room->name,
                             'date' => $booking->date->format('M d, Y'),
                             'formatted_time' => $booking->formatted_time,
                             'user_name' => $booking->user_name,
                             'attendees' => $booking->attendees,
                             'status' => $booking->status,
                         ]) }})">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $booking->formatted_time }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $booking->date->format('F d, Y') }}</p>
                            </div>
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm text-gray-500">No upcoming reservations</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right Column: Calendar -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <!-- Calendar Navigation -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <button @click="prevMonth()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @click="nextMonth()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button @click="goToToday()" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            today
                        </button>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900" x-text="monthNames[currentMonth] + ' ' + currentYear"></h2>
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                        <button @click="calendarView = 'month'" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="calendarView === 'month' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            month
                        </button>
                        <button @click="calendarView = 'week'" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="calendarView === 'week' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            week
                        </button>
                        <button @click="calendarView = 'list'" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="calendarView === 'list' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            list
                        </button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <!-- Day Headers -->
                    <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                        <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                            <div class="py-3 text-center text-sm font-semibold text-gray-600" x-text="day"></div>
                        </template>
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7">
                        <template x-for="(week, weekIndex) in calendarWeeks" :key="weekIndex">
                            <template x-for="(day, dayIndex) in week" :key="weekIndex + '-' + dayIndex">
                                <div class="min-h-[100px] border-b border-r border-gray-200 p-2"
                                     :class="{
                                         'bg-gray-50': !day.isCurrentMonth,
                                         'bg-yellow-50': day.isToday
                                     }">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium"
                                              :class="day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400'"
                                              x-text="day.day"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <template x-for="event in day.events.slice(0, 2)" :key="event.id">
                                            <div class="relative group">
                                                <div class="text-xs px-1.5 py-0.5 bg-blue-100 text-blue-700 rounded truncate cursor-pointer hover:bg-blue-200 transition-colors"
                                                     @click="openViewBookingModal(event)"
                                                     x-text="event.formatted_time?.split(' - ')[0] + ' ' + event.title"></div>
                                                <!-- Hover Tooltip -->
                                                <div class="absolute left-0 bottom-full mb-2 z-50 hidden group-hover:block w-64 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3 pointer-events-none">
                                                    <div class="font-semibold text-sm mb-2" x-text="event.title || event.room_name"></div>
                                                    <div class="space-y-1.5 text-gray-300">
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                            </svg>
                                                            <span x-text="event.room_name"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                            </svg>
                                                            <span x-text="event.formatted_time"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                            </svg>
                                                            <span x-text="event.user_name"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            </svg>
                                                            <span x-text="event.attendees + ' attendees'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="absolute left-4 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="day.events.length > 2">
                                            <button @click="openDayEventsModal(day)" 
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium pl-1 hover:underline" 
                                                    x-text="'+' + (day.events.length - 2) + ' more'"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- View Booking Details Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showViewModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl transform transition-all" 
                 @click.stop
                 x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Booking Details</h3>
                                <p class="text-blue-100 text-sm" x-text="selectedBooking?.room_name"></p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <template x-if="selectedBooking">
                        <div class="space-y-4">
                            <!-- Title -->
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Title</p>
                                    <p class="text-gray-900 font-semibold" x-text="selectedBooking.title || 'No title'"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Date -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Date</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.date"></p>
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Time</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.formatted_time"></p>
                                    </div>
                                </div>

                                <!-- Booked By -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Booked By</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.user_name"></p>
                                    </div>
                                </div>

                                <!-- Attendees -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Attendees</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.attendees + ' people'"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Status</p>
                                    </div>
                                </div>
                                <span class="px-3 py-1.5 rounded-full text-sm font-semibold"
                                      :class="{
                                          'bg-green-100 text-green-700': selectedBooking.status === 'approved',
                                          'bg-amber-100 text-amber-700': selectedBooking.status === 'pending',
                                          'bg-red-100 text-red-700': selectedBooking.status === 'rejected',
                                          'bg-gray-100 text-gray-700': selectedBooking.status === 'cancelled'
                                      }"
                                      x-text="selectedBooking.status?.charAt(0).toUpperCase() + selectedBooking.status?.slice(1)"></span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100">
                    <button @click="showViewModal = false" 
                            class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Day Events Modal (for +X more) -->
    <div x-show="showDayEventsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showDayEventsModal = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl transform transition-all" 
                 @click.stop
                 x-show="showDayEventsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">All Bookings</h3>
                                <p class="text-indigo-100 text-sm" x-text="selectedDay?.date ? new Date(selectedDay.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : ''"></p>
                            </div>
                        </div>
                        <button @click="showDayEventsModal = false" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-4 max-h-96 overflow-y-auto">
                    <div class="space-y-3">
                        <template x-for="event in selectedDay?.events || []" :key="event.id">
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors border border-gray-100"
                                 @click="openViewBookingModal(event); showDayEventsModal = false;">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900" x-text="event.room_name"></h4>
                                            <p class="text-sm text-gray-600 mt-0.5" x-text="event.title || 'No title'"></p>
                                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span x-text="event.formatted_time"></span>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <span x-text="event.user_name"></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="shrink-0 px-2 py-1 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-700': event.status === 'approved',
                                              'bg-amber-100 text-amber-700': event.status === 'pending',
                                              'bg-red-100 text-red-700': event.status === 'rejected',
                                              'bg-gray-100 text-gray-700': event.status === 'cancelled'
                                          }"
                                          x-text="event.status?.charAt(0).toUpperCase() + event.status?.slice(1)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-2xl border-t border-gray-100">
                    <button @click="showDayEventsModal = false" 
                            class="w-full px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Booking Modal -->
    <div x-show="showBookingModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeBookingModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Schedule New Booking</h2>
                                <p class="text-teal-100 text-sm">Fill in the details below to schedule a new room booking</p>
                            </div>
                        </div>
                        <button @click="closeBookingModal()" class="text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitBooking()" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column: Booking Information -->
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                                <span class="w-1 h-4 bg-teal-600 rounded"></span>
                                Booking Information
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Reservation Title <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </span>
                                        <input type="text" x-model="bookingForm.title" required
                                               placeholder="e.g., Team Meeting, Client Presentation"
                                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Book for User <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </span>
                                        <input type="text" x-model="bookingForm.user_name" required
                                               placeholder="Search and select a user..."
                                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <div class="relative">
                                        <textarea x-model="bookingForm.description" rows="3"
                                                  placeholder="Add any additional details about the booking..."
                                                  class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"></textarea>
                                        <button type="button" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Schedule & Room -->
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                                <span class="w-1 h-4 bg-teal-600 rounded"></span>
                                Schedule & Room
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Room <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                        </span>
                                        <select x-model="bookingForm.room_id" required
                                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 appearance-none bg-white">
                                            <option value="">Select a room</option>
                                            @foreach($rooms as $room)
                                            <option value="{{ $room->id }}">{{ $room->name }} (Capacity: {{ $room->capacity }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </span>
                                        <input type="date" x-model="bookingForm.date" required
                                               min="{{ now()->format('Y-m-d') }}"
                                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Start Time <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </span>
                                            <input type="time" x-model="bookingForm.start_time" required
                                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            End Time <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </span>
                                            <input type="time" x-model="bookingForm.end_time" required
                                                   class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Number of Attendees <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                            </svg>
                                        </span>
                                        <input type="number" x-model="bookingForm.attendees" min="1" required
                                               class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                        <button type="button" @click="closeBookingModal()"
                                class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            <span class="flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Cancel
                            </span>
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                            <span class="flex items-center gap-2">
                                <svg x-show="!isSubmitting" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                <svg x-show="isSubmitting" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                <span x-text="isSubmitting ? 'Creating...' : 'Create Booking'"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function dashboardApp() {
    return {
        showBookingModal: false,
        isSubmitting: false,
        showBookingModal: false,
        showViewModal: false,
        showDayEventsModal: false,
        selectedBooking: null,
        selectedDay: null,
        isSubmitting: false,
        calendarView: 'month',
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        calendarData: @json($calendarData),
        monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 
                     'July', 'August', 'September', 'October', 'November', 'December'],
        
        bookingForm: {
            title: '',
            room_id: '',
            date: '{{ now()->format("Y-m-d") }}',
            start_time: '09:00',
            end_time: '10:00',
            attendees: 1,
            user_name: '',
            description: '',
        },

        get calendarWeeks() {
            const weeks = [];
            const firstDay = new Date(this.currentYear, this.currentMonth, 1);
            const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
            const startPadding = firstDay.getDay();
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let currentWeek = [];
            
            // Previous month days
            const prevMonth = new Date(this.currentYear, this.currentMonth, 0);
            for (let i = startPadding - 1; i >= 0; i--) {
                currentWeek.push({
                    day: prevMonth.getDate() - i,
                    isCurrentMonth: false,
                    isToday: false,
                    events: []
                });
            }

            // Current month days
            for (let i = 1; i <= lastDay.getDate(); i++) {
                const date = new Date(this.currentYear, this.currentMonth, i);
                const dateStr = this.formatDateKey(date);
                const isToday = date.getTime() === today.getTime();
                
                currentWeek.push({
                    day: i,
                    date: dateStr,
                    isCurrentMonth: true,
                    isToday,
                    events: this.calendarData[dateStr] || []
                });

                if (currentWeek.length === 7) {
                    weeks.push(currentWeek);
                    currentWeek = [];
                }
            }

            // Next month days
            let nextMonthDay = 1;
            while (currentWeek.length < 7 && currentWeek.length > 0) {
                currentWeek.push({
                    day: nextMonthDay++,
                    isCurrentMonth: false,
                    isToday: false,
                    events: []
                });
            }
            if (currentWeek.length > 0) {
                weeks.push(currentWeek);
            }

            // Ensure we have 6 weeks for consistent height
            while (weeks.length < 6) {
                const week = [];
                for (let i = 0; i < 7; i++) {
                    week.push({
                        day: nextMonthDay++,
                        isCurrentMonth: false,
                        isToday: false,
                        events: []
                    });
                }
                weeks.push(week);
            }

            return weeks;
        },

        init() {
            this.fetchCalendarData();
        },

        formatDateKey(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        },

        async fetchCalendarData() {
            try {
                const response = await fetch(`/rooms/calendar/month?month=${this.currentMonth + 1}&year=${this.currentYear}`);
                this.calendarData = await response.json();
            } catch (error) {
                console.error('Failed to fetch calendar data:', error);
            }
        },

        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.fetchCalendarData();
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.fetchCalendarData();
        },

        goToToday() {
            const today = new Date();
            this.currentMonth = today.getMonth();
            this.currentYear = today.getFullYear();
            this.fetchCalendarData();
        },

        openBookingModal() {
            this.bookingForm = {
                title: '',
                room_id: '',
                date: '{{ now()->format("Y-m-d") }}',
                start_time: '09:00',
                end_time: '10:00',
                attendees: 1,
                user_name: '',
                description: '',
            };
            this.showBookingModal = true;
        },

        closeBookingModal() {
            this.showBookingModal = false;
        },

        openViewBookingModal(booking) {
            this.selectedBooking = booking;
            this.showViewModal = true;
        },

        openDayEventsModal(day) {
            this.selectedDay = day;
            this.showDayEventsModal = true;
        },

        viewBooking(booking) {
            this.openViewBookingModal(booking);
        },

        async submitBooking() {
            this.isSubmitting = true;
            try {
                const response = await fetch('{{ route("reservations.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.bookingForm)
                });

                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    this.closeBookingModal();
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to create booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the booking');
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>
@endpush
@endsection
