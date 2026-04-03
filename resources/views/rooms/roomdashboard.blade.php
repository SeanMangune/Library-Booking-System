<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Booking Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
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
                    @php
                        $pendingWidth = ($stats['pending'] > 0 ? min(100, $stats['pending'] * 20) : 0) . '%';
                        $approvedWidth = ($stats['approved'] > 0 ? min(100, $stats['approved'] * 20) : 0) . '%';
                        $rejectedWidth = ($stats['rejected'] > 0 ? min(100, $stats['rejected'] * 20) : 0) . '%';
                    @endphp
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Pending -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-amber-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'pending'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="w-6 h-6 text-amber-600 fa-icon fa-solid fa-clock text-2xl leading-none"></i>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                @php $pendingWidth = ($stats['pending'] > 0 ? min(100, $stats['pending'] * 20) : 0) . '%'; @endphp
                                {!! '<div class="h-full bg-gradient-to-r from-amber-400 to-orange-400 rounded-full" style="width: ' . $pendingWidth . '"></div>' !!}
                            </div>
                        </div>

                        <!-- Approved -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-emerald-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'approved'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-green-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="w-6 h-6 text-emerald-600 fa-icon fa-solid fa-circle-check text-2xl leading-none"></i>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                @php $approvedWidth = ($stats['approved'] > 0 ? min(100, $stats['approved'] * 20) : 0) . '%'; @endphp
                                {!! '<div class="h-full bg-gradient-to-r from-emerald-400 to-green-400 rounded-full" style="width: ' . $approvedWidth . '"></div>' !!}
                            </div>
                        </div>

                        <!-- Rejected -->
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-5 hover:shadow-lg hover:border-red-200 transition-all duration-300 cursor-pointer"
                             @click="activeTab = 'rejected'; filterBookings()">
                            <div class="flex items-center justify-between">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-red-100 to-rose-100 flex items-center justify-center group-hover:scale-110 transition-transform">
                                    <i class="w-6 h-6 text-red-500 fa-icon fa-solid fa-circle-xmark text-2xl leading-none"></i>
                                </div>
                                <span class="text-3xl font-bold text-gray-900">{{ $stats['rejected'] }}</span>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-600">Rejected</p>
                            <div class="mt-2 h-1 bg-gray-100 rounded-full overflow-hidden">
                                @php $rejectedWidth = ($stats['rejected'] > 0 ? min(100, $stats['rejected'] * 20) : 0) . '%'; @endphp
                                {!! '<div class="h-full bg-gradient-to-r from-red-400 to-rose-400 rounded-full" style="width: ' . $rejectedWidth . '"></div>' !!}
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
                                 'room_capacity' => $booking->room->standardBookingCapacityLimit(),
                                 'user_name' => $booking->user_name,
                                 'user_email' => $booking->user_email,
                                 'date' => $booking->date->format('M j, Y'),
                                 'formatted_date' => $booking->formatted_date,
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
                                            <i class="w-3.5 h-3.5 fa-icon fa-solid fa-triangle-exclamation text-sm leading-none"></i>
                                            Conflict
                                        </span>
                                        @endif
                                        
                                        @if($booking->exceedsCapacity())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                            <i class="w-3.5 h-3.5 fa-icon fa-solid fa-users text-sm leading-none"></i>
                                            Over Capacity
                                        </span>
                                        @endif

                                        @if($booking->requiresCapacityPermission())
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                            <i class="w-3.5 h-3.5 fa-icon fa-solid fa-circle-info text-sm leading-none"></i>
                                            Needs Librarian Capacity Exception
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
                                        {{ $booking->attendees }} / {{ $booking->room->standardBookingCapacityLimit() }}
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
                                <i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-calendar-days text-3xl leading-none"></i>
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
                                <i class="w-5 h-5 text-gray-600 fa-icon fa-solid fa-chevron-right text-xl leading-none"></i>
                            </button>
                            <h3 class="text-sm font-semibold text-gray-900" x-text="monthNames[currentMonth] + ' ' + currentYear"></h3>
                            <button @click="nextMonth()" class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="w-5 h-5 text-gray-600 fa-icon fa-solid fa-chevron-right text-xl leading-none"></i>
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
        <x-modals.roomdashboard.booking-details />
    </div>

    <script type="application/json" id="dashboard-data">
        {!! json_encode([
            'tab' => $tab ?? request()->get('tab', 'all'),
            'stats' => $stats,
            'bookings' => $bookings,
        ]) !!}
    </script>
    <script>
    function bookingDashboard() {
        // Parse data from JSON script block
        const data = JSON.parse(document.getElementById('dashboard-data').textContent);
        return {
                showModal: this.$persist(false).as('staff_showModal'),
                selectedBooking: this.$persist(null).as('staff_selectedBooking'),
                activeTab: this.$persist(data.tab).as('staff_activeTab'),
                isLoading: false,
                actionType: null,
                allowCapacityException: false,
                showExceptionInput: false,
                exceptionReason: '',
                // Calendar
                currentMonth: this.$persist(new Date().getMonth()).as('staff_currentMonth'),
                currentYear: this.$persist(new Date().getFullYear()).as('staff_currentYear'),
                selectedDate: this.$persist(null).as('staff_selectedDate'),
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
                    
                    // Previous month days (include full date and booking counts)
                    const prevMonth = new Date(this.currentYear, this.currentMonth, 0);
                    for (let i = startPadding - 1; i >= 0; i--) {
                        const dayNum = prevMonth.getDate() - i;
                        const dateObj = new Date(prevMonth.getFullYear(), prevMonth.getMonth(), dayNum);
                        const dateStr = this.formatDateKey(dateObj);

                        days.push({
                            day: dayNum,
                            date: dateStr,
                            isCurrentMonth: false,
                            isToday: dateObj.getTime() === today.getTime(),
                            isSelected: false,
                            bookingCount: this.calendarData[dateStr]?.length || 0
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
                    
                    // Next month days (include full date and booking counts)
                    const remaining = 42 - days.length;
                    for (let i = 1; i <= remaining; i++) {
                        const dateObj = new Date(this.currentYear, this.currentMonth + 1, i);
                        const dateStr = this.formatDateKey(dateObj);

                        days.push({
                            day: i,
                            date: dateStr,
                            isCurrentMonth: false,
                            isToday: dateObj.getTime() === today.getTime(),
                            isSelected: false,
                            bookingCount: this.calendarData[dateStr]?.length || 0
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
                    this.filterBookings();
                    if (this.showModal) {
                        document.body.style.overflow = 'hidden';
                    }
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
                            window.notifyApp?.('success', 'Booking approved successfully.');
                            window.setTimeout(() => {
                                window.location.reload();
                            }, 850);
                        } else {
                            window.notifyApp?.('error', 'Failed to approve booking');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.notifyApp?.('error', 'An error occurred');
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
                            window.notifyApp?.('success', 'Booking rejected successfully.');
                            window.setTimeout(() => {
                                window.location.reload();
                            }, 850);
                        } else {
                            window.notifyApp?.('error', 'Failed to reject booking');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        window.notifyApp?.('error', 'An error occurred');
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
