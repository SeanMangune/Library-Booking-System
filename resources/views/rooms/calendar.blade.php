@extends('layouts.app')

@section('title', 'Calendar - SmartSpace')

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
<div x-data="calendarApp()" x-init="init()" class="lg:h-[calc(100dvh-9rem)] lg:overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:h-full lg:min-h-0">
        <!-- Main Calendar -->
        <div class="lg:col-span-3 lg:min-h-0 lg:flex lg:flex-col">
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
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:flex-1 lg:min-h-0 lg:flex lg:flex-col">
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
                <div class="h-[68vh] lg:h-auto lg:flex-1 lg:min-h-0 overflow-auto">
                    <div id="calendar" class="fc-custom h-full"></div>
                </div>
            </div>
        </div>

        <!-- Rooms Sidebar -->
        <div class="lg:col-span-1 lg:min-h-0">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:h-full lg:flex lg:flex-col lg:min-h-0">
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
                <div class="space-y-2 max-h-[calc(100vh-300px)] lg:max-h-none lg:flex-1 lg:min-h-0 overflow-y-auto">
                    @foreach($rooms as $room)
                    <div class="room-item p-3 rounded-lg cursor-pointer transition-colors"
                         :class="selectedRoom?.id == {{ $room->id }} ? 'bg-blue-50 border border-blue-200' : 'hover:bg-gray-50 border border-transparent'"
                        @click.stop.prevent="selectRoom({ id: {{ $room->id }}, name: '{{ addslashes($room->name) }}', capacity: {{ $room->standardBookingCapacityLimit() }} })"
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
                                @if($room->isCollaborative())
                                    <p class="text-xs text-gray-500">Base Capacity: 10 (up to 12 with librarian approval)</p>
                                @else
                                    <p class="text-xs text-gray-500">Capacity: {{ $room->capacity }}</p>
                                @endif
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
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="closeBookingModal()"></div>
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

                                <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 space-y-2">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Identity Verification
                                    </label>
                                    <p class="text-xs text-gray-600">
                                        Booking access is available for students, employees, and alumni. QC ID details are managed through the signup registration flow.
                                    </p>
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
                                     <input type="number" x-model="bookingForm.attendees" min="1" :max="attendeeInputMax || null" required
                                           class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                                     <p x-show="attendeeGuidance" x-cloak class="mt-1 text-xs text-gray-500" x-text="attendeeGuidance"></p>
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
                                class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
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

    <!-- Booking Success Modal -->
    <div x-show="showSuccessModal" x-cloak class="modal p-4" :class="{ 'modal-open': showSuccessModal }" @keydown.escape.window="closeSuccessModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- <div class="bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-7 rounded-t-2xl text-center">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-white"
                        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>
                    <p class="text-emerald-100 text-sm mt-1" x-text="successMessage"></p>
                </div> -->

                <div class="success-header">
    <div class="success-icon-wrap">
        <svg class="success-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <h2 class="success-title"
        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>

    <p class="success-text" x-text="successMessage"></p>
</div>

<style>
/* Header container */
.success-header{
    background: linear-gradient(to right, #0d9488, #059669); /* teal-600 -> emerald-600 */
    padding: 1.75rem 1.5rem; /* px-6 py-7 */
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem; /* rounded-t-2xl */
    text-align: center;
}

/* Icon circle */
.success-icon-wrap{
    width: 56px;   /* w-14 */
    height: 56px;  /* h-14 */
    margin: 0 auto 0.75rem auto; /* mx-auto mb-3 */
    background: rgba(255,255,255,0.2); /* bg-white/20 */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Check icon */
.success-icon{
    width: 32px;  /* w-8 */
    height: 32px; /* h-8 */
    color: #ffffff;
}

/* Title */
.success-title{
    font-size: 1.125rem; /* text-lg */
    font-weight: 700;    /* font-bold */
    color: #ffffff;
    margin: 0;
}

/* Subtitle */
.success-text{
    font-size: 0.875rem; /* text-sm */
    margin-top: 0.25rem;
    color: #d1fae5; /* emerald-100 */
}
</style>


                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="bg-gray-50 rounded-xl p-4 mb-5">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Room</p>
                                <p class="font-semibold text-gray-900" x-text="successBooking?.room?.name || selectedRoom?.name || '-'"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Date</p>
                                <p class="font-semibold text-gray-900" x-text="formatDate(successBooking?.date) || bookingForm.date"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Time</p>
                                <p class="font-semibold text-gray-900" x-text="formatTimeRange(successBooking?.start_time, successBooking?.end_time) || (bookingForm.start_time + ' - ' + bookingForm.end_time)"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Status</p>
                                <p class="font-semibold" :class="successBooking?.status === 'approved' ? 'text-emerald-600' : 'text-amber-600'"
                                   x-text="(successBooking?.status || 'pending').toString().charAt(0).toUpperCase() + (successBooking?.status || 'pending').toString().slice(1)"></p>
                            </div>
                        </div>
                    </div>

                    <template x-if="successBooking?.qr_code_url">
                        <div class="text-center mb-5">
                            <h3 class="text-sm font-semibold text-gray-700 mb-3">QR Code</h3>
                            <div class="inline-block p-3 bg-white border border-gray-200 rounded-xl shadow-sm">
                                <img :src="successBooking.qr_code_url" alt="Booking QR Code" class="w-40 h-40 mx-auto">
                            </div>
                            <div class="mt-3">
                                <a :href="successBooking.qr_code_url" download
                                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Download
                                </a>
                            </div>
                        </div>
                    </template>

                    <button @click="closeSuccessModal()"
                            class="w-full px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-medium transition-colors">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Detail Modal -->
    <div x-show="showEventModal" x-cloak class="modal p-4" :class="{ 'modal-open': showEventModal }" @keydown.escape.window="closeEventModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
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
                <div class="p-6 flex-1 min-h-0 overflow-y-auto">
                    <div class="space-y-4">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Purpose</p>
                            <p class="font-semibold text-gray-900" x-text="selectedEvent?.purpose || selectedEvent?.title || 'No purpose provided'"></p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.room_name"></p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs font-medium text-gray-500 mb-1">Date</p>
                                <p class="font-semibold text-gray-900" x-text="selectedEvent?.formatted_date || selectedEvent?.date"></p>
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
        showSuccessModal: false,
        successMessage: '',
        successBooking: null,
        selectedEvent: null,
        isSubmitting: false,
        hasVerifiedRegistration: @json($hasVerifiedRegistration),
        verifiedRegistrationName: @json($verifiedRegistration?->full_name),
        isStaffUser: @json(auth()->user()?->isStaff() ?? false),
        rooms: @json($roomOptions),
        qcIdFile: null,
        qcIdPreviewUrl: '',
        qcIdIsProcessing: false,
        qcIdProgress: 0,
        qcIdStatusMessage: '',
        qcIdError: '',
        qcIdVerification: null,
        
        bookingForm: {
            purpose: '',
            room_id: '{{ $selectedRoom?->id ?? "" }}',
            date: '{{ now()->format("Y-m-d") }}',
            start_time: '09:00',
            end_time: '10:00',
            attendees: 1,
            user_name: '',
            user_email: '',
            description: '',
            qc_id_ocr_text: '',
            qc_id_cardholder_name: '',
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

            this.$watch('bookingForm.user_name', (value) => {
                if (this.hasVerifiedRegistration) {
                    return;
                }

                if (!this.qcIdVerification?.cardholder_name) {
                    return;
                }

                if (!this.namesMatch(value, this.qcIdVerification.cardholder_name)) {
                    this.qcIdVerification = null;
                    this.bookingForm.qc_id_cardholder_name = '';
                    this.bookingForm.qc_id_ocr_text = '';
                    this.qcIdError = 'The booking name changed after verification. Please upload the QC ID again.';
                }
            });

            this.$watch('bookingForm.room_id', () => {
                const max = this.attendeeInputMax;
                if (max && Number(this.bookingForm.attendees) > Number(max)) {
                    this.bookingForm.attendees = max;
                }
            });

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    confidence_score: 100,
                    source: 'registration',
                };
            }
        },

        get selectedRoomMeta() {
            return this.rooms.find(room => String(room.id) === String(this.bookingForm.room_id)) || null;
        },

        get attendeeInputMax() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return null;
            }

            return this.isStaffUser ? room.capacity : room.student_limit;
        },

        get attendeeGuidance() {
            const room = this.selectedRoomMeta;

            if (!room) {
                return '';
            }

            if (!room.is_collaborative) {
                return `Room capacity: ${room.capacity} attendees.`;
            }

            if (this.isStaffUser) {
                return `Collaborative room capacity: ${room.capacity} attendees.`;
            }

            if (room.student_limit > room.standard_limit) {
                return `Collaborative rooms allow up to ${room.standard_limit} attendees by default. Requests up to ${room.student_limit} attendees need librarian approval.`;
            }

            return `This collaborative room allows up to ${room.standard_limit} attendees.`;
        },

        normalizeName(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[^A-Z\s]/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        normalizeOcrText(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/\r/g, '')
                .replace(/[^A-Z0-9,./\-\n\s]/g, ' ')
                .replace(/[ \t]+/g, ' ')
                .replace(/\n{2,}/g, '\n')
                .trim();
        },

        async buildQcCanvas(file) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const scale = Math.max(1, 2800 / Math.max(img.width, img.height));
                    canvas.width = Math.round(img.width * scale);
                    canvas.height = Math.round(img.height * scale);
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    for (let i = 0; i < data.length; i += 4) {
                        const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                        const contrast = Math.min(255, Math.max(0, ((gray - 128) * 1.7) + 128));
                        data[i] = contrast;
                        data[i + 1] = contrast;
                        data[i + 2] = contrast;
                    }
                    ctx.putImageData(imageData, 0, 0);

                    resolve(canvas);
                };
                img.onerror = () => resolve(null);
                img.src = URL.createObjectURL(file);
            });
        },

        createQcCropCanvas(sourceCanvas, rect, threshold = false) {
            const crop = document.createElement('canvas');
            const sx = Math.max(0, Math.round(sourceCanvas.width * rect.x));
            const sy = Math.max(0, Math.round(sourceCanvas.height * rect.y));
            const sw = Math.max(1, Math.round(sourceCanvas.width * rect.w));
            const sh = Math.max(1, Math.round(sourceCanvas.height * rect.h));

            crop.width = sw;
            crop.height = sh;

            const ctx = crop.getContext('2d');
            ctx.drawImage(sourceCanvas, sx, sy, sw, sh, 0, 0, sw, sh);

            if (threshold) {
                const imageData = ctx.getImageData(0, 0, sw, sh);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const value = data[i] > 145 ? 255 : 0;
                    data[i] = value;
                    data[i + 1] = value;
                    data[i + 2] = value;
                }
                ctx.putImageData(imageData, 0, 0);
            }

            return crop;
        },

        async recognizeQcCanvas(canvas, config = {}, withProgress = false) {
            const options = {
                preserve_interword_spaces: '1',
                ...config,
            };

            if (withProgress) {
                options.logger = (message) => {
                    if (message.status) {
                        this.qcIdStatusMessage = message.status;
                    }

                    if (typeof message.progress === 'number') {
                        this.qcIdProgress = message.progress * 100;
                    }
                };
            }

            const result = await window.Tesseract.recognize(canvas, 'eng', options);

            return this.normalizeOcrText(result?.data?.text || '');
        },

        async collectQcOcrText(file) {
            const enhancedCanvas = await this.buildQcCanvas(file);
            if (!enhancedCanvas) {
                throw new Error('Unable to prepare the QC ID image for OCR.');
            }

            const fullText = await this.recognizeQcCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 6,
            }, true);

            const sparseText = await this.recognizeQcCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 11,
            });

            // Focused crops boost key fields that generic OCR may blur.
            const bottomStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.62, y: 0.76, w: 0.34, h: 0.14 }, true);
            const dateStrip = this.createQcCropCanvas(enhancedCanvas, { x: 0.25, y: 0.39, w: 0.48, h: 0.15 }, true);

            const bottomText = await this.recognizeQcCanvas(bottomStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789 ',
            });

            const dateText = await this.recognizeQcCanvas(dateStrip, {
                tessedit_pageseg_mode: 7,
                tessedit_char_whitelist: '0123456789/ -',
            });

            return this.normalizeOcrText([fullText, sparseText, dateText, bottomText].filter(Boolean).join('\n'));
        },

        namesMatch(first, second) {
            const firstTokens = this.normalizeName(first).split(' ').filter(token => token.length >= 2);
            const secondTokens = this.normalizeName(second).split(' ').filter(token => token.length >= 2);

            if (!firstTokens.length || !secondTokens.length) {
                return false;
            }

            const overlap = firstTokens.filter(token => secondTokens.includes(token));
            const threshold = Math.min(firstTokens.length, secondTokens.length);

            return threshold <= 2 ? overlap.length === threshold : overlap.length >= 2;
        },

        resetQcIdState({ keepPreview = true } = {}) {
            this.qcIdIsProcessing = false;
            this.qcIdProgress = 0;
            this.qcIdStatusMessage = '';
            this.qcIdError = '';
            this.qcIdVerification = null;
            this.bookingForm.qc_id_ocr_text = '';
            this.bookingForm.qc_id_cardholder_name = '';

            if (!keepPreview) {
                if (this.qcIdPreviewUrl) {
                    URL.revokeObjectURL(this.qcIdPreviewUrl);
                }

                this.qcIdPreviewUrl = '';
                this.qcIdFile = null;
            }
        },

        async handleQcIdUpload(event) {
            const file = event.target?.files?.[0];
            this.resetQcIdState({ keepPreview: false });

            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.qcIdError = 'Please upload an image file for the QC ID.';
                return;
            }

            this.qcIdFile = file;
            this.qcIdPreviewUrl = URL.createObjectURL(file);

            await this.runQcIdVerification(file);
        },

        async reprocessQcId() {
            if (!this.qcIdFile) {
                this.qcIdError = 'Upload a QC ID image first.';
                return;
            }

            this.resetQcIdState();
            await this.runQcIdVerification(this.qcIdFile);
        },

        async runQcIdVerification(file) {
            if (!window.Tesseract) {
                this.qcIdError = 'OCR is not available right now. Please refresh the page and try again.';
                return;
            }

            this.qcIdIsProcessing = true;
            this.qcIdStatusMessage = 'Reading QC ID image...';
            this.qcIdProgress = 0;

            try {
                this.qcIdStatusMessage = 'Enhancing image for OCR...';
                const extractedText = await this.collectQcOcrText(file);
                if (!extractedText) {
                    throw new Error('No readable text was found in the uploaded QC ID image.');
                }

                this.bookingForm.qc_id_ocr_text = extractedText;
                this.qcIdStatusMessage = 'Validating QC ID format...';

                const response = await fetch('/rooms/qc-id/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ocr_text: extractedText,
                        user_name: this.bookingForm.user_name,
                    }),
                });

                const payload = await response.json();
                const v = payload.verification || null;

                // Always populate with whatever was detected
                this.qcIdVerification = v;
                if (v?.cardholder_name) {
                    this.bookingForm.qc_id_cardholder_name = v.cardholder_name;
                    this.bookingForm.user_name = v.cardholder_name;
                }

                if (!payload.success) {
                    this.qcIdError = payload.message || 'The uploaded image is not recognized as a QC ID.';
                    return;
                }

                this.qcIdError = '';
                this.qcIdProgress = 100;
                this.qcIdStatusMessage = 'QC ID verified.';
            } catch (error) {
                console.error('QC ID verification failed:', error);
                this.qcIdError = error?.message || 'Unable to read the QC ID image. Please upload a clearer photo.';
                this.qcIdVerification = null;
                this.bookingForm.qc_id_cardholder_name = '';
                this.bookingForm.qc_id_ocr_text = '';
            } finally {
                this.qcIdIsProcessing = false;
            }
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
                    const props = info.event.extendedProps || {};

                    // derive a friendly date for the modal (prefer server-provided formatted value)
                    const derivedDate = props.formatted_date || props.date || self.formatDate(info.event.start);

                    // derive a friendly time range (prefer server-provided formatted_time)
                    let derivedTime = props.formatted_time;
                    if (!derivedTime && info.event.start && info.event.end) {
                        const s = `${String(info.event.start.getHours()).padStart(2,'0')}:${String(info.event.start.getMinutes()).padStart(2,'0')}`;
                        const e = `${String(info.event.end.getHours()).padStart(2,'0')}:${String(info.event.end.getMinutes()).padStart(2,'0')}`;
                        derivedTime = self.formatTimeRange(s, e);
                    }

                    self.selectedEvent = {
                        id: info.event.id,
                        title: info.event.title,
                        purpose: props.purpose || info.event.title,
                        room_name: props.room_name || props.room,
                        date: derivedDate,
                        formatted_date: props.formatted_date || derivedDate,
                        formatted_time: derivedTime || '',
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
                    const props = info.event.extendedProps || {};
                    const selfRef = self;

                    // Avoid the browser default tooltip (we render our own)
                    info.el.removeAttribute('title');

                    const onEnter = () => selfRef.showEventTooltip(info, props);
                    const onLeave = () => selfRef.hideEventTooltip();

                    info.el.addEventListener('mouseenter', onEnter);
                    info.el.addEventListener('mouseleave', onLeave);
                    info.el.addEventListener('focusin', onEnter);
                    info.el.addEventListener('focusout', onLeave);

                    info.el.__tooltipHandlers = { onEnter, onLeave };
                },
                eventWillUnmount: function(info) {
                    const handlers = info.el.__tooltipHandlers;
                    if (handlers) {
                        info.el.removeEventListener('mouseenter', handlers.onEnter);
                        info.el.removeEventListener('mouseleave', handlers.onLeave);
                        info.el.removeEventListener('focusin', handlers.onEnter);
                        info.el.removeEventListener('focusout', handlers.onLeave);
                    }
                    if (self.tooltipAnchorEl === info.el) {
                        self.hideEventTooltip();
                    }
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
            this.qcIdError = '';

            if (this.hasVerifiedRegistration) {
                this.qcIdVerification = {
                    is_valid: true,
                    cardholder_name: this.verifiedRegistrationName || '',
                    confidence_score: 100,
                    source: 'registration',
                };
            }

            this.showBookingModal = true;
        },

        closeBookingModal() {
            this.qcIdError = '';
            this.showBookingModal = false;
        },

        closeEventModal() {
            this.showEventModal = false;
            this.selectedEvent = null;
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.successMessage = '';
            this.successBooking = null;
            // Refresh the page after modal closes
            window.location.reload();
        },

        formatDate(value) {
            if (!value) return '';
            const d = new Date(value);
            if (Number.isNaN(d.getTime())) return String(value);
            return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        },

        formatTime(value) {
            if (!value) return '';
            const parts = String(value).split(':');
            if (parts.length < 2) return String(value);
            const h = parseInt(parts[0], 10);
            const m = parseInt(parts[1], 10);
            if (Number.isNaN(h) || Number.isNaN(m)) return String(value);
            const d = new Date();
            d.setHours(h, m, 0, 0);
            // force AM/PM display regardless of browser locale
            return d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', hour12: true });
        },

        tooltipEl: null,
        tooltipAnchorEl: null,
        tooltipCleanup: null,

        showEventTooltip(info, props) {
            this.hideEventTooltip();

            const title = info?.event?.title || '';
            const purpose = props.purpose || title;
            const roomName = props.room_name || props.room || '';
            const time = props.formatted_time || '';
            const userName = props.user_name || props.userName || '';
            const attendees = props.attendees != null ? String(props.attendees) : '';

            const el = document.createElement('div');
            el.className = 'fixed z-50 w-72 bg-gray-900 text-white text-xs rounded-lg shadow-xl p-3';
            el.style.pointerEvents = 'none';

            el.innerHTML = `
                <div class="font-semibold text-sm mb-2">${this.escapeHtml(purpose || roomName)}</div>
                <div class="space-y-1.5 text-gray-300">
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span>${this.escapeHtml(roomName)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>${this.escapeHtml(time)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span>${this.escapeHtml(userName)}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>${this.escapeHtml(attendees ? attendees + ' attendees' : '')}</span>
                    </div>
                </div>
                <div data-arrow class="absolute left-6 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900"></div>
            `;

            document.body.appendChild(el);

            const anchor = info?.el;
            if (!anchor) {
                el.remove();
                return;
            }

            this.tooltipEl = el;
            this.tooltipAnchorEl = anchor;

            const position = () => {
                if (!this.tooltipEl || !this.tooltipAnchorEl) return;
                const rect = this.tooltipAnchorEl.getBoundingClientRect();
                const tipRect = this.tooltipEl.getBoundingClientRect();

                const viewportW = window.innerWidth;
                const viewportH = window.innerHeight;
                const padding = 8;
                const gap = 10;

                let left = rect.left + rect.width / 2;
                const half = tipRect.width / 2;
                left = Math.max(padding + half, Math.min(viewportW - padding - half, left));

                let top = rect.top - tipRect.height - gap;
                let placeBelow = false;
                if (top < padding) {
                    top = rect.bottom + gap;
                    placeBelow = true;
                }
                if (top + tipRect.height > viewportH - padding) {
                    top = Math.max(padding, viewportH - padding - tipRect.height);
                }

                this.tooltipEl.style.left = `${left}px`;
                this.tooltipEl.style.top = `${top}px`;
                this.tooltipEl.style.transform = 'translateX(-50%)';

                const arrow = this.tooltipEl.querySelector('[data-arrow]');
                if (arrow) {
                    if (placeBelow) {
                        arrow.className = 'absolute left-6 bottom-full w-0 h-0 border-l-8 border-r-8 border-b-8 border-transparent border-b-gray-900';
                    } else {
                        arrow.className = 'absolute left-6 top-full w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-900';
                    }
                }
            };

            position();

            const onScrollOrResize = () => position();
            window.addEventListener('scroll', onScrollOrResize, true);
            window.addEventListener('resize', onScrollOrResize);
            this.tooltipCleanup = () => {
                window.removeEventListener('scroll', onScrollOrResize, true);
                window.removeEventListener('resize', onScrollOrResize);
            };
        },

        hideEventTooltip() {
            if (this.tooltipCleanup) {
                this.tooltipCleanup();
            }
            this.tooltipCleanup = null;
            this.tooltipAnchorEl = null;

            if (this.tooltipEl) {
                this.tooltipEl.remove();
            }
            this.tooltipEl = null;
        },

        escapeHtml(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        },

        async submitBooking() {
            this.isSubmitting = true;
            try {
                const response = await fetch('/rooms/room-reservations', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.bookingForm)
                });

                const data = await response.json();
                
                if (response.ok && data.success) {
                    this.successMessage = data.message || 'Booking created successfully.';
                    this.successBooking = data.booking || null;
                    this.closeBookingModal();
                    this.showSuccessModal = true;
                } else {
                    this.qcIdError = data.message || 'Failed to create booking';
                    alert(data.message || 'Failed to create booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the booking');
            } finally {
                this.isSubmitting = false;
            }
        },
    }
}
</script>
@endpush
@endsection
