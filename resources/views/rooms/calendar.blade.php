@extends('layouts.app')

@section('title', 'Calendar | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
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
                        {{-- <p class="text-sm text-gray-500 mt-1">Manage bookings and view room status</p> --}}
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="openBookingModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium rounded-lg transition-colors">
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

    <!-- Booking Success Modal -->
    <div x-show="showSuccessModal" x-cloak class="modal p-4" :class="{ 'modal-open': showSuccessModal }" @keydown.escape.window="closeSuccessModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <!-- <div class="bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-7 rounded-t-2xl text-center">
                    <div class="w-14 h-14 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-3">
                        <i class="w-8 h-8 text-white fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
                    </div>
                    <h2 class="text-lg font-bold text-white"
                        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>
                    <p class="text-emerald-100 text-sm mt-1" x-text="successMessage"></p>
                </div> -->

                <div class="success-header">
    <div class="success-icon-wrap">
        <i class="success-icon fa-icon fa-solid fa-circle-check text-[2rem] leading-none"></i>
    </div>

    <h2 class="success-title"
        x-text="successBooking?.status === 'pending' ? 'Booking Submitted!' : 'Booking Confirmed!'"></h2>

    <p class="success-text" x-text="successMessage"></p>
</div>

<style>
/* Header container */
.success-header{
    background: linear-gradient(to right, #0d9488, #059669); /* teal-600 → emerald-600 */
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
                                <p class="font-semibold text-gray-900" x-text="successBooking?.room?.name || selectedRoom?.name || '—'"></p>
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
                                    <i class="w-4 h-4 fa-icon fa-solid fa-arrow-up-from-bracket text-base leading-none"></i>
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
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40 transition-opacity" @click="closeSuccessModal()">close</button>
    </div>

    <!-- Event Detail Modal -->
    <div x-show="showEventModal" x-cloak class="modal p-4" :class="{ 'modal-open': showEventModal }" @keydown.escape.window="closeEventModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-white rounded-2xl shadow-2xl max-h-[88vh] overflow-hidden flex flex-col" @click.stop>
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white">Booking Details</h2>
                        <button @click="closeEventModal()" class="text-white/80 hover:text-white">
                            <i class="w-6 h-6 fa-icon fa-solid fa-xmark text-2xl leading-none"></i>
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
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeEventModal()">close</button>
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
window.roomCalendarConfig = {
    selectedRoom: @json($selectedRoom),
    hasVerifiedRegistration: @json($hasVerifiedRegistration),
    verifiedRegistrationName: @json($verifiedRegistration?->full_name),
    isStaffUser: @json(auth()->user()?->isStaff() ?? false),
    rooms: @json($roomOptions),
    defaultRoomId: @json($selectedRoom?->id),
    defaultDate: '{{ now()->format("Y-m-d") }}',
    eventsUrl: '{{ route("calendar.events") }}',
    verifyQcIdUrl: '{{ route("qcid.verify") }}',
    storeBookingUrl: '{{ route("reservations.store") }}',
};
</script>
@endpush
@endsection
