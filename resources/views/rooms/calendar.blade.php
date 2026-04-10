@extends('layouts.app')

@section('title', 'Calendar | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Calendar</span>
@endsection

@section('content')
<div x-data="calendarApp(window.roomCalendarConfig)" x-init="init()" class="lg:h-[calc(100dvh-9rem)] lg:overflow-hidden">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:h-full lg:min-h-0">
        <!-- Main Calendar -->
        <div class="lg:col-span-3 lg:min-h-0 lg:flex lg:flex-col">
            <!-- Room Header -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl border border-indigo-500/20 shadow-lg p-5 sm:p-6 mb-6 relative overflow-hidden group/header">
                <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/header:scale-110 transition-transform duration-500 pointer-events-none">
                    <i class="fa-solid fa-calendar-check text-8xl text-white"></i>
                </div>
                <div class="relative z-10 w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md shadow-lg hidden sm:flex shrink-0">
                            <i class="w-6 h-6 text-white fa-icon fa-solid fa-building text-2xl leading-none"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-extrabold text-white tracking-tight" x-text="selectedRoom?.name || 'All Rooms'"></h1>
                            <p class="text-indigo-100 mt-1 text-sm sm:text-base">View availability and schedule bookings.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="openBookingModal()"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-white hover:bg-gray-50 text-indigo-700 text-sm font-semibold rounded-xl transition-all shadow-md">
                            <i class="w-4 h-4 fa-icon fa-solid fa-plus text-base leading-none"></i>
                            Create Booking
                        </button>
                    </div>
                </div>
            </div>

            <!-- Calendar -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 lg:flex-1 lg:min-h-0 lg:flex lg:flex-col">
                <!-- Calendar Header -->
                <div class="mb-6 grid grid-cols-1 gap-3 lg:grid-cols-[auto_minmax(0,1fr)_auto] lg:items-center">
                    <div class="order-2 flex items-end justify-center gap-2 sm:justify-start lg:order-1 lg:items-center">
                        <button @click="calendar?.prev()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="w-4 h-4 text-gray-600 fa-icon fa-solid fa-chevron-left text-base leading-none"></i>
                        </button>
                        <button @click="calendar?.next()" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors">
                            <i class="w-4 h-4 text-gray-600 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
                        </button>
                        <button @click="calendar?.today()" class="px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                            today
                        </button>
                    </div>
                    <h2 class="order-1 text-center text-xl font-semibold text-gray-900 lg:order-2 lg:px-4" x-text="calendarTitle"></h2>
                    <div class="order-3 flex items-center justify-center sm:justify-end lg:order-3">
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
                    <i class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 fa-icon fa-solid fa-magnifying-glass text-base leading-none"></i>
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
                                <i class="w-5 h-5 text-gray-500 fa-icon fa-solid fa-building text-xl leading-none"></i>
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
                                <i class="w-5 h-5 text-blue-600 shrink-0 fa-icon fa-solid fa-circle-check text-xl leading-none"></i>
                            </template>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal -->
    <x-modals.calendar.booking :rooms="$rooms" />

    <!-- Booking Success Modal -->
    <x-modals.calendar.success />

    <!-- Event Detail Modal -->
    <x-modals.calendar.event-details />
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
@vite(['resources/css/app.css', 'resources/js/app.js'])
@php
    $verifiedRegistration = auth()->user()?->qcidRegistration()->where('verification_status', 'verified')->first();
    $hasVerifiedRegistration = $verifiedRegistration !== null;
    $verifiedRegistrationQcidNumber = $verifiedRegistration?->qcid_number;

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
<script type="application/json" id="room-calendar-config">
{!! json_encode([
    'selectedRoom' => $selectedRoom,
    'hasVerifiedRegistration' => $hasVerifiedRegistration,
    'verifiedRegistrationName' => $verifiedRegistration?->full_name,
    'userName' => auth()->user()?->name ?? '',
    'userEmail' => auth()->user()?->email ?? '',
    'isStaffUser' => auth()->user()?->isStaff() ?? false,
    'rooms' => $roomOptions,
    'defaultRoomId' => $selectedRoom?->id,
    'defaultDate' => now()->format('Y-m-d'),
    'eventsUrl' => route('calendar.events'),
    'availabilityUrl' => route('calendar.availability'),
    'staffUserLookupUrl' => route('rooms.users.search'),
    'verifyQcIdUrl' => route('qcid.verify'),
    'storeBookingUrl' => route('reservations.store'),
]) !!}
</script>
<script>
window.roomCalendarConfig = JSON.parse(document.getElementById('room-calendar-config').textContent);
</script>
@endpush
@endsection
