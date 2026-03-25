@extends('layouts.app')

@section('title', 'Dashboard | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Rooms</span>
@endsection

@section('content')
<div x-data="dashboardApp()" x-init="init()" class="flex flex-col xl:h-[calc(100dvh-9rem)] xl:overflow-hidden">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Rooms Overview</h1>
            {{-- <p class="text-sm text-gray-500 mt-1">View collab room bookings from today through the next two weeks, plus the calendar overview.</p> --}}
        </div>
        <button @click="openBookingModal()" 
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors">
            <i class="w-4 h-4 fa-icon fa-solid fa-plus text-base leading-none"></i>
            Create Booking
        </button>
    </div>

    <div class="grid grid-cols-1 gap-6 transition-all duration-300 xl:flex-1 xl:min-h-0"
         :class="bookingsPanelOpen ? 'xl:grid-cols-[minmax(0,1fr)_23rem]' : 'xl:grid-cols-1'">
        <!-- Left Column: Calendar -->
        <div class="min-w-0 xl:min-h-0">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 xl:h-full xl:flex xl:flex-col xl:min-h-0">
                <!-- Calendar Navigation -->
                <div class="mb-6 grid grid-cols-1 gap-3 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-center">
                    <div class="order-2 flex items-end justify-center gap-2 sm:justify-start lg:order-1 lg:items-center">
                        <button @click="prevMonth()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="w-4 h-4 text-gray-600 fa-icon fa-solid fa-chevron-left text-base leading-none"></i>
                        </button>
                        <button @click="nextMonth()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="w-4 h-4 text-gray-600 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
                        </button>
                        <button @click="goToToday()" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            today
                        </button>
                    </div>

                    <h2 class="order-1 text-center text-xl font-semibold text-gray-900 lg:order-2 lg:px-4" x-text="calendarTitle"></h2>

                    <div class="order-3 flex flex-wrap items-center justify-center gap-2 sm:justify-end lg:order-3">
                        <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            <button @click="changeDashboardView('dayGridMonth')"
                                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                    :class="calendarView === 'dayGridMonth' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                month
                            </button>
                            <button @click="changeDashboardView('timeGridWeek')"
                                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                    :class="calendarView === 'timeGridWeek' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                week
                            </button>
                            <button @click="changeDashboardView('listWeek')"
                                    class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                    :class="calendarView === 'listWeek' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                                list
                            </button>
                        </div>

                        <button type="button"
                                @click="toggleBookingsPanel()"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium border border-gray-200 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors whitespace-nowrap"
                                :title="bookingsPanelOpen ? 'Collapse bookings panel' : 'Expand bookings panel'">
                            <i class="w-4 h-4 fa-icon fa-solid text-base leading-none"
                               :class="bookingsPanelOpen ? 'fa-angles-right' : 'fa-angles-left'"></i>
                            <span x-text="bookingsPanelOpen ? 'Hide bookings' : 'Show bookings'"></span>
                        </button>
                    </div>
                </div>

                <div class="border border-gray-200 rounded-lg overflow-auto xl:flex-1 xl:min-h-0" x-show="calendarView === 'dayGridMonth'">
                    <div class="grid grid-cols-7 bg-gray-50 border-b border-gray-200">
                        <template x-for="day in ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']" :key="day">
                            <div class="py-3 text-center text-sm font-semibold text-gray-600" x-text="day"></div>
                        </template>
                    </div>

                    <div class="grid grid-cols-7">
                        <template x-for="(week, weekIndex) in calendarWeeks" :key="weekIndex">
                            <template x-for="(day, dayIndex) in week" :key="weekIndex + '-' + dayIndex">
                                <div class="min-h-[100px] border-b border-r border-gray-200 p-2"
                                     @click="openBookingModalForDay(day)"
                                     :class="{
                                         'bg-gray-50': !day.isCurrentMonth,
                                         'bg-yellow-50': day.isToday,
                                         'cursor-pointer hover:bg-teal-50 transition-colors': day.isCurrentMonth
                                     }">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-sm font-medium"
                                              :class="day.isCurrentMonth ? 'text-gray-900' : 'text-gray-400'"
                                              x-text="day.day"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <template x-for="event in day.events.slice(0, 2)" :key="event.id">
                                            <div class="relative group">
                                                   <div class="text-xs px-1.5 py-0.5 bg-green-100 text-green-700 rounded truncate cursor-pointer hover:bg-green-200 transition-colors"
                                                     @click.stop="openViewBookingModal(event)"
                                                     x-text="event.formatted_time?.split(' - ')[0] + ' ' + event.title"></div>
                                                <div class="absolute left-0 bottom-full mb-2 z-50 hidden group-hover:block w-64 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3 pointer-events-none">
                                                    <div class="font-semibold text-sm mb-2" x-text="event.title || event.room_name"></div>
                                                    <div class="space-y-1.5 text-gray-300">
                                                        <div class="flex items-center gap-2">
                                                            <i class="w-3.5 h-3.5 text-gray-400 fa-icon fa-solid fa-building text-sm leading-none"></i>
                                                            <span x-text="event.room_name"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="w-3.5 h-3.5 text-gray-400 fa-icon fa-solid fa-clock text-sm leading-none"></i>
                                                            <span x-text="event.formatted_time"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="w-3.5 h-3.5 text-gray-400 fa-icon fa-solid fa-user text-sm leading-none"></i>
                                                            <span x-text="event.user_name"></span>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <i class="w-3.5 h-3.5 text-gray-400 fa-icon fa-solid fa-users text-sm leading-none"></i>
                                                            <span x-text="event.attendees + ' attendees'"></span>
                                                        </div>
                                                    </div>
                                                    <div class="absolute left-4 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></div>
                                                </div>
                                            </div>
                                        </template>
                                        <template x-if="day.events.length > 2">
                                            <button @click.stop="openDayEventsModal(day)"
                                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium pl-1 hover:underline"
                                                    x-text="'+' + (day.events.length - 2) + ' more'"></button>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>

                <div class="h-[68vh] xl:h-auto border border-gray-200 rounded-lg p-3 overflow-auto xl:flex-1 xl:min-h-0" x-show="calendarView !== 'dayGridMonth'" x-cloak>
                    <div id="dashboard-calendar" class="fc-custom-dashboard h-full"></div>
                </div>
            </div>
        </div>

        <!-- Right Column: Collab Room Bookings -->
        <aside x-show="bookingsPanelOpen"
               x-cloak
               x-transition
                    class="min-w-0 xl:min-h-0">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm h-full xl:flex xl:flex-col xl:min-h-0">
                <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Collab Room Bookings</h2>
                        <p class="text-xs text-gray-500 mt-1">Today to next 2 weeks</p>
                    </div>
                    <button type="button"
                            @click="toggleBookingsPanel()"
                            class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-colors"
                            title="Collapse bookings panel">
                        <i class="w-4 h-4 fa-icon fa-solid fa-xmark text-base leading-none"></i>
                    </button>
                </div>
                <div class="p-4 max-h-[34rem] xl:max-h-none xl:flex-1 xl:min-h-0 overflow-y-auto">
                    @forelse($collabRoomBookings as $booking)
                    <div class="py-3 px-2 rounded-lg hover:bg-gray-50 transition-colors cursor-pointer {{ $loop->last ? '' : 'border-b border-gray-200' }}"
                         @click="viewBooking({{ json_encode([
                             'id' => $booking->id,
                             'title' => $booking->title,
                             'room_name' => $booking->room->name,
                             'date' => $booking->date->format('M d, Y'),
                             'formatted_date' => $booking->formatted_date,
                             'formatted_time' => $booking->formatted_time,
                             'user_name' => $booking->user_name,
                             'attendees' => $booking->attendees,
                             'status' => $booking->status,
                         ]) }})">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                                <p class="text-sm text-gray-500 mt-0.5">{{ $booking->formatted_time }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    @if($booking->date->isToday())
                                        Today
                                    @else
                                        {{ $booking->date->format('F d, Y') }}
                                    @endif
                                </p>
                            </div>
                            <span class="px-2.5 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <i class="w-12 h-12 text-gray-300 mx-auto mb-3 fa-icon fa-solid fa-calendar-days text-5xl leading-none"></i>
                        <p class="text-sm text-gray-500">No collaborative-room bookings in the next two weeks</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </aside>
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
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="w-5 h-5 text-white fa-icon fa-solid fa-calendar-days text-xl leading-none"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Schedule New Booking</h2>
                                <p class="text-teal-100 text-sm">Fill in the details below to schedule a new room booking</p>
                            </div>
                        </div>
                        <button @click="closeBookingModal()" class="text-white/80 hover:text-white">
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
    'verifyQcIdUrl' => route('qcid.verify'),
    'storeBookingUrl' => route('reservations.store'),
]) !!}
</script>
<script>
window.dashboardCalendarConfig = JSON.parse(document.getElementById('dashboard-calendar-config').textContent);
</script>
@endpush
@endsection

