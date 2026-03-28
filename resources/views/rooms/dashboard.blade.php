@extends('layouts.app')

@section('title', 'Dashboard | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Rooms</span>
@endsection

@section('content')
<div x-data="dashboardApp()" x-init="init()" class="flex flex-col xl:h-[calc(100dvh-9rem)] xl:overflow-hidden">
    <!-- Main Dashboard Body -->
    <div class="flex-1 min-h-0 overflow-y-auto px-1 group/dashboard">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pb-8">
            
            <!-- Left Column: Quick Stats & Current Status (lg:col-span-4) -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Welcome Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 relative overflow-hidden group/welcome hover:shadow-md transition-all duration-300">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover/welcome:opacity-20 transition-opacity">
                        <i class="fa-solid fa-house-chimney text-8xl text-teal-600"></i>
                    </div>
                    <div class="relative z-10">
                        <h2 class="text-xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}!</h2>
                        <p class="text-gray-500 mt-1 text-sm leading-relaxed">Modernizing your library experience. Manage your room bookings and status from this dashboard.</p>
                        
                        <div class="mt-6 flex flex-wrap gap-2">
                            <button @click="openBookingModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-xl transition-all hover:scale-[1.02] active:scale-[0.98]">
                                <i class="fa-solid fa-plus"></i> New Booking
                            </button>
                            <a href="{{ route('reservations.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-all">
                                <i class="fa-solid fa-list"></i> My Reservations
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Status Card -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden hover:shadow-md transition-all duration-300">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <h3 class="font-bold text-gray-900 flex items-center gap-2">
                           <i class="fa-solid fa-shield-check text-teal-600"></i>
                           System Status
                        </h3>
                        <span class="flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center justify-between p-3 bg-emerald-50 rounded-xl">
                                <span class="text-xs font-semibold text-emerald-800 uppercase">Verification</span>
                                <span x-text="hasVerifiedRegistration ? 'VERIFIED' : 'PENDING'" class="text-xs font-black text-emerald-700"></span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded-xl">
                                <span class="text-xs font-semibold text-blue-800 uppercase">Bookings Today</span>
                                <span class="text-xs font-black text-blue-700 font-mono">{{ $collabRoomBookings->where('date', now()->today())->count() }} Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Tips Card -->
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden group/tips">
                    <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/tips:scale-110 transition-transform">
                        <i class="fa-solid fa-lightbulb text-9xl"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Modern SmartSpace</h3>
                    <p class="text-indigo-100 text-xs leading-relaxed opacity-90">Remember to bring your QC ID for physical verification if required by the librarian.</p>
                </div>
            </div>

            <!-- Right Column: Calendar & Bookings (lg:col-span-8) -->
            <div class="lg:col-span-8 space-y-6">
                <!-- Mini Calendar Subsection -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden flex flex-col hover:shadow-md transition-all duration-300">
                    <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-gray-900 text-lg">Availability Calendar</h3>
                            <p class="text-xs text-gray-500" x-text="calendarTitle"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1">
                                <button @click="prevMonth()" class="p-1.5 rounded-lg hover:bg-white transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                                <button @click="goToToday()" class="px-2 py-1 text-[10px] font-black uppercase text-gray-600 hover:text-gray-900">TODAY</button>
                                <button @click="nextMonth()" class="p-1.5 rounded-lg hover:bg-white transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
                            </div>
                            <div class="flex items-center gap-1 bg-gray-100 rounded-xl p-1">
                                <button @click="changeDashboardView('dayGridMonth')" :class="calendarView === 'dayGridMonth' ? 'bg-white shadow-sm text-teal-600' : 'text-gray-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">Month</button>
                                <button @click="changeDashboardView('listWeek')" :class="calendarView === 'listWeek' ? 'bg-white shadow-sm text-teal-600' : 'text-gray-500'" class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all">List</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 transition-all duration-500 min-h-[400px]">
                        <div x-show="calendarView === 'dayGridMonth'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="border border-gray-100 rounded-2xl overflow-hidden shadow-inner bg-gray-50/10">
                            <div class="grid grid-cols-7 bg-white/50 border-b border-gray-100">
                                <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                                    <div class="py-3 text-center text-[10px] font-black uppercase text-gray-400 tracking-widest" x-text="day"></div>
                                </template>
                            </div>

                            <div class="grid grid-cols-7">
                                <template x-for="(week, weekIndex) in calendarWeeks" :key="weekIndex">
                                    <template x-for="(day, dayIndex) in week" :key="weekIndex + '-' + dayIndex">
                                        <div class="min-h-[110px] sm:min-h-[130px] border-b border-r border-gray-100 p-2 relative group/day transition-all"
                                             @click="!day.isPast && day.isCurrentMonth && openBookingModalForDay(day)"
                                             :class="{
                                                 'bg-gray-50/50': !day.isCurrentMonth,
                                                 'bg-teal-50/30': day.isToday,
                                                 'cursor-pointer hover:bg-teal-50/80 hover:z-10 hover:shadow-lg': day.isCurrentMonth && !day.isPast,
                                                 'opacity-40 cursor-not-allowed grayscale': day.isPast && day.isCurrentMonth,
                                             }">
                                            <div class="flex items-center justify-between mb-2">
                                                <span class="text-xs font-black"
                                                      :class="day.isToday ? 'bg-teal-600 text-white w-6 h-6 rounded-full flex items-center justify-center -ml-1' : (day.isCurrentMonth ? 'text-gray-600' : 'text-gray-300')"
                                                      x-text="day.day"></span>
                                            </div>
                                            <div class="space-y-1 overflow-hidden">
                                                <template x-for="event in day.events.slice(0, 3)" :key="event.id">
                                                    <div class="relative group/event">
                                                           <div class="text-[10px] px-2 py-1 bg-white border border-gray-100 text-gray-700 rounded-lg shadow-sm truncate hover:border-teal-400 hover:text-teal-700 transition-all font-medium flex items-center gap-1.5"
                                                             @click.stop="openViewBookingModal(event)">
                                                                <span class="w-1.5 h-1.5 rounded-full" :class="event.status === 'approved' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                                                                <span x-text="event.formatted_time?.split(':')[0] + event.formatted_time?.slice(-2)"></span>
                                                                <span class="opacity-60">|</span>
                                                                <span x-text="event.room_name?.split(' ')[1] || event.room_name"></span>
                                                            </div>
                                                    </div>
                                                </template>
                                                <template x-if="day.events.length > 3">
                                                    <button @click.stop="openDayEventsModal(day)"
                                                            class="w-full text-[10px] text-teal-600 font-black uppercase text-center py-1 bg-teal-50/50 rounded-lg hover:bg-teal-100 transition-all"
                                                            x-text="'+' + (day.events.length - 3) + ' more'"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </template>
                            </div>
                        </div>

                        <div x-show="calendarView === 'listWeek'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="h-[500px] border border-gray-100 rounded-2xl p-4 overflow-auto bg-gray-50/10">
                            <div id="dashboard-calendar" class="fc-custom-dashboard h-full"></div>
                        </div>
                    </div>
                </div>

                <!-- Active Bookings Panel (Compact Version) -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 hover:shadow-md transition-all duration-300">
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
                                <p class="text-xs text-gray-500">{{ $booking->formatted_time }} • {{ $booking->date->format('M d') }}</p>
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

    <!-- View Booking Details Modal -->
    <div x-show="showViewModal" x-cloak class="modal p-4" :class="{ 'modal-open': showViewModal }" @keydown.escape.window="showViewModal = false">
            <div class="modal-box w-11/12 max-w-lg p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col transform transition-all" 
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
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Booking Details</h3>
                                <p class="text-blue-100 text-sm" x-text="selectedBooking?.room_name"></p>
                            </div>
                        </div>
                        <button @click="showViewModal = false" class="text-white/80 hover:text-white transition-colors">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4 flex-1 min-h-0 overflow-y-auto">
                    <template x-if="selectedBooking">
                        <div class="space-y-4">
                            <!-- Title -->
                            <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <i class="w-5 h-5 text-blue-600 fa-icon fa-solid fa-tag text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Purpose</p>
                                    <p class="text-gray-900 font-semibold" x-text="selectedBooking.title || 'No purpose provided'"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <!-- Date -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                        <i class="w-5 h-5 text-green-600 fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Date</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.formatted_date || selectedBooking.date"></p>
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                        <i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-clock text-xl leading-none"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Time</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.formatted_time"></p>
                                    </div>
                                </div>

                                <!-- Booked By -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                                        <i class="w-5 h-5 text-amber-600 fa-icon fa-solid fa-user text-xl leading-none"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Booked By</p>
                                        <p class="text-gray-900 font-semibold" x-text="selectedBooking.user_name"></p>
                                    </div>
                                </div>

                                <!-- Attendees -->
                                <div class="flex items-center gap-3 p-4 bg-gray-50 rounded-xl">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center">
                                        <i class="w-5 h-5 text-teal-600 fa-icon fa-solid fa-users text-xl leading-none"></i>
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
                                        <i class="w-5 h-5 text-indigo-600 fa-icon fa-solid fa-circle-check text-xl leading-none"></i>
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
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showViewModal = false">close</button>
    </div>

    <!-- Day Events Modal (for +X more) -->
    <div x-show="showDayEventsModal" x-cloak class="modal p-4" :class="{ 'modal-open': showDayEventsModal }" @keydown.escape.window="showDayEventsModal = false">
            <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col transform transition-all" 
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
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">All Bookings</h3>
                                <p class="text-indigo-100 text-sm" x-text="selectedDay?.date ? new Date(selectedDay.date).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) : ''"></p>
                            </div>
                        </div>
                        <button @click="showDayEventsModal = false" class="text-white/80 hover:text-white transition-colors">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-4 flex-1 min-h-0 overflow-y-auto">
                    <div class="space-y-3">
                        <template x-for="event in selectedDay?.events || []" :key="event.id">
                            <div class="p-4 bg-gray-50 rounded-xl hover:bg-gray-100 cursor-pointer transition-colors border border-gray-100"
                                 @click="openViewBookingModal(event); showDayEventsModal = false;">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                            <i class="w-5 h-5 text-blue-600 fa-icon fa-solid fa-building text-xl leading-none"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="font-semibold text-gray-900" x-text="event.room_name"></h4>
                                            <p class="text-sm text-gray-600 mt-0.5" x-text="event.title || 'No title'"></p>
                                            <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                                <span class="flex items-center gap-1">
                                                    <i class="w-3.5 h-3.5 fa-icon fa-solid fa-clock text-sm leading-none"></i>
                                                    <span x-text="event.formatted_time"></span>
                                                </span>
                                                <span class="flex items-center gap-1">
                                                    <i class="w-3.5 h-3.5 fa-icon fa-solid fa-user text-sm leading-none"></i>
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
            <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="showDayEventsModal = false">close</button>
    </div>

    <!-- Create Booking Modal -->
    <div x-show="showBookingModal" x-cloak class="modal p-4" :class="{ 'modal-open': showBookingModal }" @keydown.escape.window="closeBookingModal()">
        <div class="modal-box w-11/12 max-w-2xl p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-6 rounded-t-2xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-8 opacity-10 pointer-events-none">
                        <i class="fa-solid fa-calendar-plus text-8xl text-white"></i>
                    </div>
                    <div class="flex items-center justify-between relative z-10">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg">
                                <i class="w-6 h-6 text-white fa-icon fa-solid fa-calendar-days text-2xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-black text-white tracking-tight">User Verification Portal</h2>
                                <p class="text-teal-50 text-xs font-medium opacity-90 uppercase tracking-widest mt-0.5">ID Scanning & Verification Required</p>
                            </div>
                        </div>
                        <button @click="closeBookingModal()" class="text-white/80 hover:text-white bg-white/10 p-2 rounded-xl hover:bg-white/20 transition-all">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="submitBooking()" class="flex flex-col min-h-0">
                    <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-4">
                                <span class="w-1 h-4 bg-teal-600 rounded"></span>
                                Booking Information
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Purpose <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="bookingForm.purpose" required
                                           placeholder="e.g., Group study, Thesis consultation"
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Book for User <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="bookingForm.user_name" required
                                           placeholder="Enter user name..."
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>

                                <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            QC ID Verification <span class="text-red-500">*</span>
                                        </label>
                                        <p x-show="!hasVerifiedRegistration" class="text-xs text-gray-500">Upload a clear photo of a Quezon City Citizen ID. The system will read the card using OCR and reject non-QC IDs.</p>
                                        <p x-show="hasVerifiedRegistration" x-cloak class="text-xs text-emerald-700">QC ID already verified from your QC ID Registration.</p>
                                    </div>

                                    <input x-show="!hasVerifiedRegistration" x-cloak type="file"
                                           accept="image/png,image/jpeg,image/jpg,image/webp"
                                           @change="handleQcIdUpload($event)"
                                           class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-teal-700">

                                    <div x-show="hasVerifiedRegistration" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 p-3">
                                        <p class="text-sm font-semibold text-emerald-800">QC ID Verified</p>
                                        <p class="text-xs text-emerald-700 mt-1">Bookings will use your approved QC ID registration status.</p>
                                    </div>

                                    <div x-show="qcIdPreviewUrl" x-cloak class="rounded-lg overflow-hidden border border-gray-200 bg-white">
                                        <img :src="qcIdPreviewUrl" alt="QC ID preview" class="w-full h-44 object-cover">
                                    </div>

                                    <div x-show="qcIdIsProcessing" x-cloak class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-sm text-teal-700">
                                        <div class="flex items-center justify-between gap-3">
                                            <span x-text="qcIdStatusMessage || 'Reading QC ID…'"></span>
                                            <span class="font-semibold" x-text="Math.round(qcIdProgress || 0) + '%' "></span>
                                        </div>
                                    </div>

                                    <div x-show="qcIdError" x-cloak class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="qcIdError"></div>

                                    <div x-show="qcIdVerification?.is_valid" x-cloak class="rounded-lg border border-emerald-200 bg-emerald-50 p-3 space-y-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-emerald-800">QC ID verified</p>
                                                <p class="text-xs text-emerald-700" x-text="'Confidence score: ' + (qcIdVerification?.confidence_score ?? 0) + '%' "></p>
                                            </div>
                                            <button type="button"
                                                    @click="reprocessQcId()"
                                                    class="inline-flex items-center rounded-lg border border-emerald-300 px-3 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 transition-colors">
                                                Re-read ID
                                            </button>
                                        </div>

                                        <dl class="grid grid-cols-1 gap-2 text-xs text-emerald-900 sm:grid-cols-2">
                                            <div>
                                                <dt class="font-medium text-emerald-700">Cardholder</dt>
                                                <dd x-text="qcIdVerification?.cardholder_name || '—'"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-emerald-700">Birth Date</dt>
                                                <dd x-text="qcIdVerification?.date_of_birth || '—'"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-emerald-700">Date Issued</dt>
                                                <dd x-text="qcIdVerification?.date_issued || '—'"></dd>
                                            </div>
                                            <div>
                                                <dt class="font-medium text-emerald-700">Valid Until</dt>
                                                <dd x-text="qcIdVerification?.valid_until || '—'"></dd>
                                            </div>
                                            <div class="sm:col-span-2">
                                                <dt class="font-medium text-emerald-700">Address</dt>
                                                <dd x-text="qcIdVerification?.address || '—'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <!-- <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        User Email <span class="text-red-500">*</span>
                                    </label>
                                    <input type="email" x-model="bookingForm.user_email" required
                                           placeholder="Enter user email..."
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div> -->

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea x-model="bookingForm.description" rows="3"
                                              placeholder="Add any additional details..."
                                              class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
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
                                    <select x-model="bookingForm.room_id" required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select a room</option>
                                        @foreach($rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->name }} (Capacity: {{ $room->standardBookingCapacityLimit() }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="bookingForm.date" required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select a date</option>
                                        <template x-for="dateOption in bookingDateOptions" :key="dateOption.value">
                                            <option :value="dateOption.value" x-text="dateOption.label"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Time Slot <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="bookingForm.time_slot" required
                                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select a time slot</option>
                                        <template x-for="slot in bookingTimeSlots" :key="slot.value">
                                            <option :value="slot.value" x-text="slot.label"></option>
                                        </template>
                                    </select>
                                    <div x-show="isLoadingTimeConflictSuggestions" x-cloak class="mt-2 text-xs text-gray-500">
                                        Checking nearby available slots...
                                    </div>
                                    <div x-show="timeConflictMessage || timeConflictSuggestions.length" x-cloak class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                        <p class="text-xs font-medium text-amber-800" x-text="timeConflictMessage"></p>

                                        <div x-show="timeConflictSuggestions.length" x-cloak class="mt-2 flex flex-wrap gap-2">
                                            <template x-for="suggestedSlot in timeConflictSuggestions" :key="suggestedSlot.value">
                                                <button type="button"
                                                        @click="applySuggestedTimeSlot(suggestedSlot.value)"
                                                        class="inline-flex items-center rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-800 hover:bg-amber-100 transition-colors"
                                                        x-text="suggestedSlot.label"></button>
                                            </template>
                                        </div>
                                    </div>
                                    {{-- <p class="mt-1 text-xs text-gray-500">One-hour slots from 8:00 AM to 5:00 PM.</p> --}}
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Number of Attendees <span class="text-red-500">*</span>
                                    </label>
                                     <input type="number" x-model="bookingForm.attendees" min="1" :max="attendeeInputMax || null" required
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                     <p x-show="attendeeGuidance" x-cloak class="mt-1 text-xs text-gray-500" x-text="attendeeGuidance"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-white shrink-0">
                        <p x-show="!hasVerifiedRegistration && !qcIdVerification?.is_valid" x-cloak class="mr-auto text-sm text-amber-600">
                            Upload and verify a QC ID before creating the booking.
                        </p>
                        <button type="button" @click="closeBookingModal()"
                                class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting || (!hasVerifiedRegistration && !qcIdVerification?.is_valid)"
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="flex items-center gap-2">
                                <i x-show="isSubmitting" class="animate-spin w-4 h-4 fa-icon fa-solid fa-spinner text-base leading-none"></i>
                                <span x-text="isSubmitting ? 'Creating...' : 'Create Booking'"></span>
                            </span>
                        </button>
                    </div>
                </form>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeBookingModal()">close</button>
    </div>
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
<script>
window.dashboardCalendarConfig = {
    hasVerifiedRegistration: @json($hasVerifiedRegistration),
    verifiedRegistrationName: @json($verifiedRegistration?->full_name),
    isStaffUser: @json(auth()->user()?->isStaff() ?? false),
    rooms: @json($roomOptions),
    defaultDate: '{{ now()->format("Y-m-d") }}',
    initialCalendarData: @json($calendarData),
    monthDataUrl: '{{ route("calendar.month") }}',
    eventsUrl: '{{ route("calendar.events") }}',
    verifyQcIdUrl: '{{ route("qcid.verify") }}',
    storeBookingUrl: '{{ route("reservations.store") }}',
};
</script>
@endpush
@endsection

