@extends('layouts.app')

@section('title', 'Calendar - Library Booking System')

@section('breadcrumb')
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-500">Rooms</span>
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-700 font-medium">Calendar</span>
@endsection

@section('content')
<div x-data="calendarApp()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Calendar -->
        <div class="lg:col-span-3">
            <!-- Room Header -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900" x-text="selectedRoom?.name || 'All Rooms'"></h1>
                        <p class="text-sm text-gray-500 mt-1">Manage bookings and view room status</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium text-gray-700 transition-colors">
                                <span class="flex items-center gap-2">
                                    Status (1)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-10">
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" checked class="rounded text-blue-600">
                                    <span class="text-sm text-gray-700">Approved</span>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-2 hover:bg-gray-50 cursor-pointer">
                                    <input type="checkbox" class="rounded text-blue-600">
                                    <span class="text-sm text-gray-700">Pending</span>
                                </label>
                            </div>
                        </div>
                        <button @click="openBookingModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Create Booking
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-2">
                        <button @click="calendar?.prev()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button @click="calendar?.next()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                        <button @click="calendar?.today()" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            today
                        </button>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900" x-text="calendarTitle"></h2>
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                        <button @click="changeView('dayGridMonth')" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="currentView === 'dayGridMonth' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            month
                        </button>
                        <button @click="changeView('timeGridWeek')" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="currentView === 'timeGridWeek' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            week
                        </button>
                        <button @click="changeView('listWeek')" 
                                class="px-3 py-1.5 text-sm font-medium rounded-md transition-colors"
                                :class="currentView === 'listWeek' ? 'bg-white shadow text-gray-900' : 'text-gray-600 hover:text-gray-900'">
                            list
                        </button>
                    </div>
                </div>

                <!-- FullCalendar Container -->
                <div id="calendar" class="fc-custom"></div>
            </div>
        </div>

        <!-- Rooms Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 sticky top-24">
                <h2 class="text-lg font-semibold text-gray-900 mb-1">Rooms</h2>
                <p class="text-sm text-gray-500 mb-4">Select a room</p>
                
                <!-- Search -->
                <div class="relative mb-4">
                    <input type="text" x-model="roomSearch" placeholder="Search rooms..."
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <!-- Room List -->
                <div class="space-y-2 max-h-[calc(100vh-300px)] overflow-y-auto">
                    @foreach($rooms as $room)
                    <div class="room-item p-3 rounded-lg cursor-pointer transition-colors"
                         :class="selectedRoom?.id == {{ $room->id }} ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50 border border-transparent'"
                         @click.stop.prevent="selectRoom({ id: {{ $room->id }}, name: '{{ addslashes($room->name) }}', capacity: {{ $room->capacity }} })"
                         data-name="{{ strtolower($room->name) }}"
                         style="position: relative; z-index: 10;">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-900 truncate">{{ $room->name }}</p>
                                <p class="text-xs text-gray-500">Capacity: {{ $room->capacity }}</p>
                            </div>
                            <template x-if="selectedRoom?.id == {{ $room->id }}">
                                <svg class="w-5 h-5 text-blue-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </template>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
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
                        <!-- Left Column -->
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
                                    <input type="text" x-model="bookingForm.title" required
                                           placeholder="e.g., Team Meeting"
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
                                        <option value="{{ $room->id }}">{{ $room->name }} (Capacity: {{ $room->capacity }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" x-model="bookingForm.date" required min="{{ now()->format('Y-m-d') }}"
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            Start Time <span class="text-red-500">*</span>
                                        </label>
                                        <input type="time" x-model="bookingForm.start_time" required
                                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">
                                            End Time <span class="text-red-500">*</span>
                                        </label>
                                        <input type="time" x-model="bookingForm.end_time" required
                                               class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        Number of Attendees <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" x-model="bookingForm.attendees" min="1" required
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-end gap-3 mt-6 pt-6 border-t border-gray-200">
                        <button type="button" @click="closeBookingModal()"
                                class="px-4 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50">
                            <span class="flex items-center gap-2">
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

    <!-- Event Detail Modal -->
    <div x-show="showEventModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeEventModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl" @click.stop>
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">Booking Details</h2>
                        <button @click="closeEventModal()" class="text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Title</p>
                            <p class="font-semibold text-gray-900" x-text="selectedEvent?.title || 'No title'"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.room_name"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Date</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.date"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Time</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.formatted_time || 'N/A'"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Attendees</p>
                                <p class="font-semibold text-gray-900" x-text="(selectedEvent?.attendees || 0) + ' people'"></p>
                            </div>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Booked By</p>
                            <p class="font-semibold text-gray-900" x-text="selectedEvent?.user_name"></p>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <p class="text-xs font-medium text-gray-500 mb-1">Status</p>
                            </div>
                            <span class="px-3 py-1.5 rounded-full text-sm font-semibold"
                                  :class="{
                                      'bg-green-100 text-green-700': selectedEvent?.status === 'approved',
                                      'bg-amber-100 text-amber-700': selectedEvent?.status === 'pending',
                                      'bg-red-100 text-red-700': selectedEvent?.status === 'rejected',
                                      'bg-gray-100 text-gray-700': selectedEvent?.status === 'cancelled'
                                  }"
                                  x-text="selectedEvent?.status?.charAt(0).toUpperCase() + selectedEvent?.status?.slice(1)"></span>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button @click="closeEventModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.fc-custom .fc-toolbar-title {
    display: none;
}
.fc-custom .fc-header-toolbar {
    display: none;
}
.fc-custom .fc-day-today {
    background-color: #FEF9C3 !important;
}
.fc-custom .fc-event {
    border-radius: 4px;
    padding: 2px 4px;
    font-size: 12px;
}
</style>
@endpush

@push('scripts')
<script>
function calendarApp() {
    return {
        calendar: null,
        calendarTitle: '',
        currentView: 'dayGridMonth',
        selectedRoom: @json($selectedRoom),
        roomSearch: '',
        showBookingModal: false,
        showEventModal: false,
        selectedEvent: null,
        isSubmitting: false,
        
        bookingForm: {
            title: '',
            room_id: '{{ $selectedRoom?->id ?? "" }}',
            date: '{{ now()->format("Y-m-d") }}',
            start_time: '09:00',
            end_time: '10:00',
            attendees: 1,
            user_name: '',
            description: '',
        },

        init() {
            this.$nextTick(() => {
                this.initCalendar();
            });

            // Room search filter
            this.$watch('roomSearch', (value) => {
                const query = value.toLowerCase();
                document.querySelectorAll('.room-item').forEach(item => {
                    const name = item.dataset.name;
                    item.style.display = name.includes(query) ? '' : 'none';
                });
            });
        },

        initCalendar() {
            const calendarEl = document.getElementById('calendar');
            if (!calendarEl) return;

            const self = this;
            this.calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: false,
                height: 'auto',
                events: this.fetchEvents.bind(this),
                eventClick: function(info) {
                    const props = info.event.extendedProps;
                    self.selectedEvent = {
                        id: info.event.id,
                        title: info.event.title,
                        room_name: props.room_name || props.room,
                        date: props.date,
                        formatted_time: props.formatted_time,
                        user_name: props.user_name || props.userName,
                        attendees: props.attendees,
                        status: props.status,
                        description: props.description,
                    };
                    self.showEventModal = true;
                },
                dateClick: function(info) {
                    self.bookingForm.date = info.dateStr;
                    self.openBookingModal();
                },
                datesSet: function(info) {
                    const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                                   'July', 'August', 'September', 'October', 'November', 'December'];
                    const date = info.view.currentStart;
                    self.calendarTitle = months[date.getMonth()] + ' ' + date.getFullYear();
                },
                eventDidMount: function(info) {
                    // Add tooltip on hover
                    const props = info.event.extendedProps;
                    info.el.title = `${info.event.title}\n${props.room_name || props.room}\n${props.formatted_time || ''}\nBy: ${props.user_name || props.userName}\n${props.attendees} attendees`;
                },
            });
            
            this.calendar.render();
            
            // Set initial title
            const months = ['January', 'February', 'March', 'April', 'May', 'June', 
                           'July', 'August', 'September', 'October', 'November', 'December'];
            const now = new Date();
            this.calendarTitle = months[now.getMonth()] + ' ' + now.getFullYear();
        },

        async fetchEvents(info, successCallback, failureCallback) {
            try {
                const params = new URLSearchParams({
                    start: info.startStr,
                    end: info.endStr,
                });
                
                if (this.selectedRoom) {
                    params.append('room_id', this.selectedRoom.id);
                }

                const response = await fetch(`/rooms/calendar/events?${params}`);
                const events = await response.json();
                successCallback(events);
            } catch (error) {
                console.error('Failed to fetch events:', error);
                failureCallback(error);
            }
        },

        changeView(view) {
            this.currentView = view;
            this.calendar?.changeView(view);
        },

        selectRoom(room) {
            console.log('Selecting room:', room);
            this.selectedRoom = room;
            this.bookingForm.room_id = room.id;
            this.calendar?.refetchEvents();
            
            // Update URL to persist room selection
            const url = new URL(window.location);
            url.searchParams.set('room', room.id);
            window.history.pushState({}, '', url);
        },

        openBookingModal() {
            if (this.selectedRoom) {
                this.bookingForm.room_id = this.selectedRoom.id;
            }
            this.showBookingModal = true;
        },

        closeBookingModal() {
            this.showBookingModal = false;
        },

        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
        },

        async submitBooking() {
            this.isSubmitting = true;
            try {
                const response = await fetch('/rooms/room-reservations', {
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
                    this.calendar?.refetchEvents();
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
