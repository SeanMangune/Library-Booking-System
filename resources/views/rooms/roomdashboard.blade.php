<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Booking Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40 text-gray-900 antialiased">
    <div x-data="bookingDashboard()" x-init="init()" class="min-h-screen">
        <!-- Header -->
        <header class="bg-white/70 backdrop-blur-xl border-b border-gray-200/50 sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-500 bg-clip-text text-transparent">
                            Booking Management
                        </h1>
                        <p class="text-sm text-gray-500 mt-0.5">Review and manage room booking requests</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm text-gray-500">{{ now()->format('l, F j, Y') }}</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Left Column: Stats + Bookings -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Pending -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-amber-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'pending'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-400 to-orange-400 rounded-full" style="width: {{ $stats['pending'] > 0 ? min(100, $stats['pending'] * 20) : 0 }}%"></div>
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-emerald-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'approved'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-emerald-400 to-green-400 rounded-full" style="width: {{ $stats['approved'] > 0 ? min(100, $stats['approved'] * 20) : 0 }}%"></div>
                            </div>
                        </div>

                        <!-- Rejected -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-red-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'rejected'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-100 to-rose-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['rejected'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Rejected</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-red-400 to-rose-400 rounded-full" style="width: {{ $stats['rejected'] > 0 ? min(100, $stats['rejected'] * 20) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex items-center gap-2 bg-white/50 backdrop-blur rounded-xl p-1.5 border border-gray-200/50 w-fit">
                        <template x-for="tab in ['all', 'pending', 'approved', 'rejected']" :key="tab">
                            <a :href="'?tab=' + tab"
                               class="px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
                               :class="activeTab === tab 
                                   ? 'bg-white shadow-sm text-indigo-600 border border-gray-200' 
                                   : 'text-gray-600 hover:text-gray-900 hover:bg-white/50'"
                               @click.prevent="activeTab = tab; filterBookings()"
                               x-text="tab.charAt(0).toUpperCase() + tab.slice(1)">
                            </a>
                        </template>
                    </div>

                    <!-- Booking Cards -->
                    <div class="space-y-4">
                        @forelse($bookings as $booking)
                        <div class="booking-card group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-xl hover:border-indigo-100 transition-all duration-300 cursor-pointer"
                             data-status="{{ $booking->status }}"
                             @click="openModal({{ json_encode([
                                 'id' => $booking->id,
                                 'room_name' => $booking->room->name,
                                 'room_capacity' => $booking->room->capacity,
                                 'user_name' => $booking->user_name,
                                 'user_email' => $booking->user_email,
                                 'date' => $booking->date->format('M j, Y'),
                                 'time' => $booking->time,
                                 'duration' => $booking->duration,
                                 'attendees' => $booking->attendees,
                                 'status' => $booking->status,
                                 'reason' => $booking->reason,
                                 'has_conflict' => $booking->has_conflict,
                                 'conflicts_with' => $booking->conflicts_with,
                                 'exceeds_capacity' => $booking->exceedsCapacity(),
                             ]) }})">
                            
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-3 flex-wrap">
                                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                            {{ $booking->room->name }}
                                        </h3>
                                        
                                        <!-- Badges -->
                                        @if($booking->has_conflict)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Conflict
                                        </span>
                                        @endif
                                        
                                        @if($booking->exceedsCapacity())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                            </svg>
                                            Over Capacity
                                        </span>
                                        @endif
                                    </div>
                                    
                                    <p class="mt-1 text-sm text-gray-500">
                                        Requested by <span class="font-medium text-gray-700">{{ $booking->user_name }}</span>
                                    </p>
                                </div>
                                
                                <!-- Status Badge -->
                                <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold
                                    @if($booking->status === 'pending') bg-amber-50 text-amber-700 border border-amber-200
                                    @elseif($booking->status === 'approved') bg-emerald-50 text-emerald-700 border border-emerald-200
                                    @else bg-red-50 text-red-700 border border-red-200 @endif">
                                    {{ ucfirst($booking->status) }}
                                </span>
                            </div>
                            
                            <!-- Details Grid -->
                            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="bg-gray-50/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Date</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->date->format('M j, Y') }}</p>
                                </div>
                                <div class="bg-gray-50/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Time</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->time }}</p>
                                </div>
                                <div class="bg-gray-50/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Attendees</p>
                                    <p class="mt-1 text-sm font-semibold {{ $booking->exceedsCapacity() ? 'text-purple-600' : 'text-gray-900' }}">
                                        {{ $booking->attendees }} / {{ $booking->room->capacity }}
                                    </p>
                                </div>
                                <div class="bg-gray-50/50 rounded-lg p-3">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->duration }} {{ $booking->duration == 1 ? 'hour' : 'hours' }}</p>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
                            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">No bookings found</h3>
                            <p class="mt-1 text-sm text-gray-500">There are no bookings matching your current filter.</p>
                        </div>
                        @endforelse
                    </div>
                </div>

                <!-- Right Column: Calendar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sticky top-24">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-900">Room Availability</h2>
                        </div>
                        
                        <!-- Month Navigation -->
                        <div class="flex items-center justify-between mb-4">
                            <button @click="prevMonth()" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <h3 class="text-sm font-semibold text-gray-900" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                            <button @click="nextMonth()" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Day Headers -->
                        <div class="grid grid-cols-7 gap-1 mb-2">
                            <template x-for="day in ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa']" :key="day">
                                <div class="text-center text-xs font-medium text-gray-500 py-2" x-text="day"></div>
                            </template>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-1">
                            <template x-for="(day, index) in calendarDays" :key="index">
                                <button 
                                    @click="day.date && selectDate(day.date)"
                                    class="aspect-square p-1 text-sm rounded-lg transition-all duration-200 relative"
                                    :class="{
                                        'text-gray-400': !day.isCurrentMonth,
                                        'text-gray-900 hover:bg-indigo-50': day.isCurrentMonth && !day.isSelected,
                                        'bg-indigo-600 text-white hover:bg-indigo-700': day.isSelected,
                                        'ring-2 ring-indigo-300 ring-offset-1': day.isToday && !day.isSelected,
                                    }"
                                    :disabled="!day.date">
                                    <span x-text="day.day"></span>
                                    <template x-if="day.bookingCount > 0">
                                        <span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 flex gap-0.5">
                                            <span class="w-1 h-1 rounded-full" 
                                                  :class="day.isSelected ? 'bg-white' : 'bg-indigo-500'"></span>
                                            <template x-if="day.bookingCount > 1">
                                                <span class="w-1 h-1 rounded-full" 
                                                      :class="day.isSelected ? 'bg-white' : 'bg-indigo-500'"></span>
                                            </template>
                                        </span>
                                    </template>
                                </button>
                            </template>
                        </div>
                        
                        <!-- Selected Date Bookings -->
                        <div x-show="selectedDate" x-cloak class="mt-6 pt-4 border-t border-gray-100">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">
                                Bookings for <span x-text="selectedDateFormatted"></span>
                            </h4>
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                <template x-if="selectedDateBookings.length === 0">
                                    <p class="text-sm text-gray-500 py-3 text-center">No bookings for this date</p>
                                </template>
                                <template x-for="booking in selectedDateBookings" :key="booking.id">
                                    <div class="p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                                         @click="openModal(booking)">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900" x-text="booking.room_name"></span>
                                            <span class="text-xs px-2 py-0.5 rounded-full"
                                                  :class="{
                                                      'bg-amber-100 text-amber-700': booking.status === 'pending',
                                                      'bg-emerald-100 text-emerald-700': booking.status === 'approved',
                                                      'bg-red-100 text-red-700': booking.status === 'rejected'
                                                  }"
                                                  x-text="booking.status.charAt(0).toUpperCase() + booking.status.slice(1)"></span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1" x-text="booking.time + ' • ' + booking.user_name"></p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Legend -->
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    <span>Has bookings</span>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="w-4 h-4 rounded border-2 border-indigo-300"></span>
                                    <span>Today</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Booking Detail Modal -->
        <div x-show="showModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="closeModal()"></div>
            
            <!-- Modal Content -->
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden"
                     @click.stop>
                    
                    <!-- Purple Header -->
                    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-white">Booking Request Details</h2>
                                <p class="text-purple-200 text-sm">Review and take action</p>
                            </div>
                            <button @click="closeModal()" 
                                    class="p-2 rounded-lg hover:bg-white/20 transition-colors">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <!-- Status Badge (for non-pending) -->
                        <div x-show="selectedBooking?.status !== 'pending'" class="mb-4">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="{
                                      'bg-emerald-50 text-emerald-700 border border-emerald-200': selectedBooking?.status === 'approved',
                                      'bg-red-50 text-red-700 border border-red-200': selectedBooking?.status === 'rejected'
                                  }"
                                  x-text="selectedBooking?.status?.charAt(0).toUpperCase() + selectedBooking?.status?.slice(1)"></span>
                        </div>
                            
                            <!-- Capacity Exceeded Warning -->
                            <template x-if="selectedBooking?.exceeds_capacity && selectedBooking?.status === 'pending'">
                                <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl mb-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                        </svg>
                                        <span class="text-sm font-semibold text-purple-800">Capacity Exceeded</span>
                                    </div>
                                    <p class="text-sm text-purple-700 mb-3" x-text="'This booking requests ' + selectedBooking?.attendees + ' attendees but the room capacity is ' + selectedBooking?.room_capacity + '.'"></p>
                                    
                                    <!-- Show textarea when exception is being requested -->
                                    <div x-show="showExceptionInput" class="mb-3">
                                        <textarea 
                                            x-model="exceptionReason"
                                            placeholder="Enter the reason for capacity exception..."
                                            class="w-full p-3 border border-purple-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300 resize-none"
                                            rows="3"></textarea>
                                    </div>
                                    
                                    <!-- Toggle button -->
                                    <button x-show="!showExceptionInput"
                                            @click="showExceptionInput = true"
                                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        Request Exception Reason
                                    </button>
                                </div>
                            </template>
                            
                            <!-- Conflict Warning -->
                            <template x-if="selectedBooking?.has_conflict">
                                <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl">
                                    <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <div>
                                        <p class="text-sm font-medium text-red-800">Scheduling Conflict</p>
                                        <p class="text-xs text-red-600 mt-0.5" x-text="'Conflicts with booking #' + selectedBooking?.conflicts_with"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        
                        <!-- Booking Details Grid with Icons -->
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Room</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Date</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.date"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Time</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.time"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Duration</p>
                                    <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.duration"></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requester Info -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">Requestor Information</h3>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Attendees -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <div>
                                    <p class="text-xs text-gray-500">Attendees</p>
                                    <p class="text-sm font-semibold" :class="selectedBooking?.exceeds_capacity ? 'text-purple-600' : 'text-gray-900'" x-text="selectedBooking?.attendees + ' people'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-500">Room Capacity</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_capacity + ' people'"></p>
                            </div>
                        </div>
                        
                        <!-- Reason -->
                        <div x-show="selectedBooking?.reason" class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Reason for Booking</h3>
                            <p class="text-sm text-gray-600" x-text="selectedBooking?.reason"></p>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div x-show="selectedBooking?.status === 'pending'" class="flex gap-3">
                            <button @click="approveBooking()"
                                    :disabled="isLoading || (selectedBooking?.exceeds_capacity && !showExceptionInput)"
                                    class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:from-gray-400 disabled:to-gray-500">
                                <svg x-show="!isLoading || actionType !== 'approve'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg x-show="isLoading && actionType === 'approve'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : (showExceptionInput ? 'Approve Exception' : 'Approve Booking')"></span>
                            </button>
                            <button @click="rejectBooking()"
                                    :disabled="isLoading"
                                    class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg x-show="!isLoading || actionType !== 'reject'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <svg x-show="isLoading && actionType === 'reject'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="curr
                                    Epic Games
                                    Store
                                    Library
                                    Unreal Engine
                                    Fab
                                    QUICK LAUNCH
                                    
                                    
                                    Search store
                                    Discover
                                    Browse
                                    News
                                    entColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                            </button>
                        </div>
                        
                        <div x-show="selectedBooking?.status !== 'pending'" class="p-4 rounded-xl text-center"
                             :class="{
                                 'bg-emerald-50 text-emerald-700': selectedBooking?.status === 'approved',
                                 'bg-red-50 text-red-700': selectedBooking?.status === 'rejected'
                             }">
                            <p class="text-sm font-medium">
                                This booking has been <span x-text="selectedBooking?.status"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function bookingDashboard() {
            return {
                showModal: false,
                selectedBooking: null,
                activeTab: '{{ $tab ?? request()->get('tab', 'all') }}',
                isLoading: false,
                actionType: null,
                allowCapacityException: false,
                showExceptionInput: false,
                exceptionReason: '',
                
                // Calendar
                currentMonth: new Date().getMonth(),
                currentYear: new Date().getFullYear(),
                selectedDate: null,
                calendarData: {},
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 
                             'July', 'August', 'September', 'October', 'November', 'December'],
                
                // Computed
                get calendarDays() {
                    const days = [];
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                    const startPadding = firstDay.getDay();
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);
                    
                    // Previous month days
                    const prevMonth = new Date(this.currentYear, this.currentMonth, 0);
                    for (let i = startPadding - 1; i >= 0; i--) {
                        days.push({
                            day: prevMonth.getDate() - i,
                            date: null,
                            isCurrentMonth: false,
                            isToday: false,
                            isSelected: false,
                            bookingCount: 0
                        });
                    }
                    
                    // Current month days
                    for (let i = 1; i <= lastDay.getDate(); i++) {
                        const date = new Date(this.currentYear, this.currentMonth, i);
                        const dateStr = this.formatDateKey(date);
                        const isToday = date.getTime() === today.getTime();
                        const isSelected = this.selectedDate === dateStr;
                        
                        days.push({
                            day: i,
                            date: dateStr,
                            isCurrentMonth: true,
                            isToday,
                            isSelected,
                            bookingCount: this.calendarData[dateStr]?.length || 0
                        });
                    }
                    
                    // Next month days
                    const remaining = 42 - days.length;
                    for (let i = 1; i <= remaining; i++) {
                        days.push({
                            day: i,
                            date: null,
                            isCurrentMonth: false,
                            isToday: false,
                            isSelected: false,
                            bookingCount: 0
                        });
                    }
                    
                    return days;
                },
                
                get selectedDateBookings() {
                    return this.calendarData[this.selectedDate] || [];
                },
                
                get selectedDateFormatted() {
                    if (!this.selectedDate) return '';
                    const date = new Date(this.selectedDate + 'T00:00:00');
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                },
                
                // Methods
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
                        const response = await fetch(`/calendar-data?month=${this.currentMonth + 1}&year=${this.currentYear}`);
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
                    this.selectedDate = null;
                    this.fetchCalendarData();
                },
                
                nextMonth() {
                    if (this.currentMonth === 11) {
                        this.currentMonth = 0;
                        this.currentYear++;
                    } else {
                        this.currentMonth++;
                    }
                    this.selectedDate = null;
                    this.fetchCalendarData();
                },
                
                selectDate(date) {
                    this.selectedDate = this.selectedDate === date ? null : date;
                },
                
                openModal(booking) {
                    this.selectedBooking = booking;
                    this.allowCapacityException = false;
                    this.showExceptionInput = false;
                    this.exceptionReason = '';
                    this.showModal = true;
                    document.body.style.overflow = 'hidden';
                },
                
                closeModal() {
                    this.showModal = false;
                    this.selectedBooking = null;
                    document.body.style.overflow = '';
                },
                
                filterBookings() {
                    const cards = document.querySelectorAll('.booking-card');
                    cards.forEach(card => {
                        const status = card.dataset.status;
                        if (this.activeTab === 'all' || status === this.activeTab) {
                            card.style.display = '';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                },
                
                async approveBooking() {
                    if (!this.selectedBooking) return;
                    
                    this.isLoading = true;
                    this.actionType = 'approve';
                    
                    try {
                        const response = await fetch(`/bookings/${this.selectedBooking.id}/approve`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                allow_capacity_exception: this.showExceptionInput,
                                exception_reason: this.exceptionReason
                            })
                        });
                        
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Failed to approve booking');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred');
                    } finally {
                        this.isLoading = false;
                        this.actionType = null;
                    }
                },
                
                async rejectBooking() {
                    if (!this.selectedBooking) return;
                    
                    this.isLoading = true;
                    this.actionType = 'reject';
                    
                    try {
                        const response = await fetch(`/bookings/${this.selectedBooking.id}/reject`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });
                        
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            alert('Failed to reject booking');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert('An error occurred');
                    } finally {
                        this.isLoading = false;
                        this.actionType = null;
                    }
                }
            }
        }
    </script>
</body>
</html>
