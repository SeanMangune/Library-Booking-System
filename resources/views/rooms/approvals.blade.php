@extends('layouts.app')

@section('title', 'Approvals - SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-sm leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-sm leading-none"></i>
<span class="text-gray-700 font-medium">Approvals</span>
@endsection

@section('content')
<div x-data="approvalsApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Booking Approvals</h1>
            <p class="text-sm text-gray-500 mt-1">Review and manage pending booking requests</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <!-- Pending -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
<i class="w-6 h-6 text-amber-600 fa-icon fa-regular fa-clock text-lg leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
        </div>

        <!-- Approved -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
<i class="w-6 h-6 text-green-600 fa-icon fa-solid fa-circle-check text-lg leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
        </div>

        <!-- Rejected -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
<i class="w-6 h-6 text-red-500 fa-icon fa-solid fa-circle-xmark text-lg leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['rejected'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Rejected</p>
        </div>
    </div>

    <!-- Filter by Room -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('approvals.index') }}" class="flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Filter by Room:</label>
            <select name="room" onchange="this.form.submit()"
                    class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="all">All Rooms</option>
                @foreach($rooms as $room)
                <option value="{{ $room->id }}" {{ request('room') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Pending Bookings -->
    <div class="space-y-4">
        @forelse($bookings as $booking)
        @php
            $bookingData = [
                'id' => $booking->id,
                'title' => $booking->title,
                'purpose' => $booking->title,
                'room_name' => $booking->room->name,
                'room_location' => $booking->room->location,
                'room_capacity' => $booking->room->capacity,
                'date' => $booking->date->format('M j, Y'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'time' => $booking->time,
                'duration' => $booking->duration,
                'user_name' => $booking->user_name,
                'user_email' => $booking->user_email,
                'attendees' => $booking->attendees,
                'status' => $booking->status,
                'description' => $booking->description,
                'has_conflict' => $booking->has_conflict,
                'exceeds_capacity' => $booking->exceedsCapacity(),
                'requires_capacity_permission' => $booking->requiresCapacityPermission(),
                'standard_capacity_limit' => $booking->room->standardBookingCapacityLimit(),
                'student_capacity_limit' => $booking->room->maxStudentBookingCapacity(),
            ];
        @endphp
        <div class="booking-card bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer"
             x-on:click="openApprovalModal({{ Js::from($bookingData) }})">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
<i class="w-6 h-6 text-indigo-600 fa-icon fa-solid fa-building text-lg leading-none"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm text-gray-500">{{ $booking->room->location }}</span>
                            @endif
                            
                            @if($booking->has_conflict)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
<i class="w-3.5 h-3.5 fa-icon fa-solid fa-triangle-exclamation text-xs leading-none"></i>
                                Conflict
                            </span>
                            @endif
                            
                            @if($booking->exceedsCapacity())
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
<i class="w-3.5 h-3.5 fa-icon fa-solid fa-users text-xs leading-none"></i>
                                Over Capacity
                            </span>
                            @endif

                            @if($booking->requiresCapacityPermission())
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
<i class="w-3.5 h-3.5 fa-icon fa-solid fa-circle-info text-xs leading-none"></i>
                                Permission Needed
                            </span>
                            @endif
                        </div>
                        
                        @if($booking->title)
                        <p class="text-sm text-gray-700 mt-1"><span class="font-medium">Purpose:</span> {{ $booking->title }}</p>
                        @endif
                        
                        <p class="text-sm text-gray-500 mt-1">
                            Requested by <span class="font-medium text-gray-700">{{ $booking->user_name }}</span>
                        </p>
                    </div>
                </div>
                
                <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                    Pending
                </span>
            </div>
            
            <!-- Details Grid -->
            <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Date</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->date->format('M j, Y') }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Time</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->formatted_time }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Attendees</p>
                    <p class="mt-1 text-sm font-semibold {{ $booking->exceedsCapacity() ? 'text-purple-600' : 'text-gray-900' }}">
                        {{ $booking->attendees }} / {{ $booking->room->capacity }}
                    </p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Duration</p>
                    <p class="mt-1 text-sm font-semibold text-gray-900">{{ $booking->duration ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center">
            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
<i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-circle-check text-2xl leading-none"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No pending approvals</h3>
            <p class="mt-1 text-sm text-gray-500">All booking requests have been reviewed.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($bookings->hasPages())
    <div class="mt-6">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @endif

    <!-- Approval Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-bold text-white">Booking Request Details</h2>
                            <p class="text-purple-200 text-sm">Review and take action</p>
                        </div>
                        <button @click="closeModal()" class="text-white/80 hover:text-white">
<i class="w-6 h-6 fa-icon fa-solid fa-xmark text-lg leading-none"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Capacity Warning -->
                    <template x-if="selectedBooking?.requires_capacity_permission">
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                            <div class="flex items-center gap-2 mb-2">
<i class="w-5 h-5 text-blue-600 fa-icon fa-solid fa-circle-info text-base leading-none"></i>
                                <span class="text-sm font-semibold text-blue-800">Collaborative Room Permission</span>
                            </div>
                            <p class="text-sm text-blue-700 mb-3" x-text="'Collaborative rooms allow up to ' + selectedBooking?.standard_capacity_limit + ' attendees by default. This request asks for ' + selectedBooking?.attendees + ' attendees and needs librarian approval.'"></p>

                            <div x-show="showExceptionInput" class="mb-3">
                                <textarea x-model="exceptionReason"
                                          placeholder="Enter the approval note for allowing the extra attendees..."
                                          class="w-full p-3 border border-blue-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-300 resize-none"
                                          rows="3"></textarea>
                            </div>

                            <button x-show="!showExceptionInput" @click="showExceptionInput = true"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Add approval note
                            </button>
                        </div>
                    </template>

                    <template x-if="selectedBooking?.exceeds_capacity">
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl mb-4">
                            <div class="flex items-center gap-2 mb-2">
<i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-users text-base leading-none"></i>
                                <span class="text-sm font-semibold text-purple-800">Capacity Exceeded</span>
                            </div>
                            <p class="text-sm text-purple-700 mb-3" x-text="'This booking requests ' + selectedBooking?.attendees + ' attendees but the room capacity is ' + selectedBooking?.room_capacity + '.'"></p>
                            
                            <div x-show="showExceptionInput" class="mb-3">
                                <textarea x-model="exceptionReason"
                                          placeholder="Enter the reason for capacity exception..."
                                          class="w-full p-3 border border-purple-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300 resize-none"
                                          rows="3"></textarea>
                            </div>
                            
                            <button x-show="!showExceptionInput && !selectedBooking?.requires_capacity_permission" @click="showExceptionInput = true"
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Request Exception Reason
                            </button>
                        </div>
                    </template>

                    <!-- Conflict Warning -->
                    <template x-if="selectedBooking?.has_conflict">
                        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4">
<i class="w-5 h-5 text-red-500 shrink-0 mt-0.5 fa-icon fa-solid fa-triangle-exclamation text-base leading-none"></i>
                            <div>
                                <p class="text-sm font-medium text-red-800">Scheduling Conflict</p>
                                <p class="text-xs text-red-600 mt-0.5">This booking conflicts with an existing reservation.</p>
                            </div>
                        </div>
                    </template>

                    <!-- Booking Details -->
                    <div class="grid grid-cols-2 gap-3 mb-6">
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
<i class="w-5 h-5 text-purple-600 fa-icon fa-solid fa-building text-base leading-none"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Room</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
<i class="w-5 h-5 text-purple-600 fa-icon fa-regular fa-calendar text-base leading-none"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_date || selectedBooking?.date"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
<i class="w-5 h-5 text-purple-600 fa-icon fa-regular fa-clock text-base leading-none"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Time</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_time || selectedBooking?.time"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
<i class="w-5 h-5 text-purple-600 fa-icon fa-regular fa-clock text-base leading-none"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Duration</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.duration || 'N/A'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Requester Info -->
                    <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Requestor Information</h3>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm">
<i class="w-4 h-4 text-purple-500 fa-icon fa-regular fa-user text-sm leading-none"></i>
                                <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
<i class="w-4 h-4 text-purple-500 fa-icon fa-regular fa-envelope text-sm leading-none"></i>
                                <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendees -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                        <div class="flex items-center gap-2">
<i class="w-5 h-5 text-purple-500 fa-icon fa-solid fa-users text-base leading-none"></i>
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

                    <template x-if="selectedBooking?.purpose">
                        <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Purpose</h3>
                            <p class="text-sm text-gray-600" x-text="selectedBooking?.purpose"></p>
                        </div>
                    </template>

                    <!-- Description -->
                    <template x-if="selectedBooking?.description">
                        <div class="mb-6 p-4 bg-gray-50 rounded-xl">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">Description</h3>
                            <p class="text-sm text-gray-600" x-text="selectedBooking?.description"></p>
                        </div>
                    </template>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button @click="approveBooking()"
                            :disabled="isLoading || ((selectedBooking?.exceeds_capacity || selectedBooking?.requires_capacity_permission) && !showExceptionInput)"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            
                            
                            <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : (showExceptionInput ? 'Approve with Note' : 'Approve')"></span>
                        </button>
                        <button @click="rejectBooking()"
                                :disabled="isLoading"
                                class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50">
                            
                            
                            <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal with QR Code -->
    <div x-show="showSuccessModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl transform transition-all"
                 x-show="showSuccessModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 @click.stop>
                
                <!-- Success Header -->
                <!-- <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-8 rounded-t-2xl text-center">
                    <div class="w-16 h-16 mx-auto bg-white/20 rounded-full flex items-center justify-center mb-4">
<i class="w-10 h-10 text-white fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white">Booking Approved!</h2>
                    <p class="text-green-100 text-sm mt-1">The booking has been successfully approved</p>
                </div> -->

                <div class="success-header">
                <div class="icon-circle">
<i class="check-icon fa-icon fa-solid fa-circle-check text-base leading-none"></i>
                </div>

                <h2 class="success-title">Booking Approved!</h2>
                <p class="success-text">The booking has been successfully approved</p>
            </div>

<style>
.success-header{
    /* gradient like: from-green-500 to-emerald-600 */
    background: linear-gradient(to right, #22c55e, #059669);
    padding: 2rem 1.5rem; /* px-6 py-8 */
    border-top-left-radius: 1rem;
    border-top-right-radius: 1rem; /* rounded-t-2xl */
    text-align: center;
}

/* Circle icon container */
.icon-circle{
    width: 64px;   /* w-16 */
    height: 64px;  /* h-16 */
    margin: 0 auto 1rem auto; /* mx-auto mb-4 */
    background: rgba(255,255,255,0.2); /* bg-white/20 */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* SVG icon */
.check-icon{
    width: 40px; /* w-10 */
    height: 40px;
    color: #ffffff; /* text-white */
}

/* Title */
.success-title{
    font-size: 1.25rem; /* text-xl */
    font-weight: 700;   /* font-bold */
    color: #ffffff;
    margin: 0;
}

/* Subtitle */
.success-text{
    font-size: 0.875rem; /* text-sm */
    margin-top: 0.25rem;
    color: #d1fae5; /* text-green-100 */
}
</style>


                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Booking Info Summary -->
                    <div class="bg-gray-50 rounded-xl p-4 mb-6">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Room</p>
                                <p class="font-semibold text-gray-900" x-text="approvedBooking?.room?.name || approvedBooking?.room_name"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Date</p>
                                <p class="font-semibold text-gray-900" x-text="approvedBooking?.formatted_date || approvedBooking?.date"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Time</p>
                                <p class="font-semibold text-gray-900" x-text="approvedBooking?.formatted_time || approvedBooking?.time || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-gray-500">Booking Code</p>
                                <p class="font-semibold text-purple-600" x-text="approvedBooking?.booking_code || 'Generating...'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code Section -->
                    <div class="text-center mb-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">Booking QR Code</h3>
                        <div class="inline-block p-4 bg-white border-2 border-gray-200 rounded-xl shadow-sm">
                            <template x-if="approvedBooking?.qr_code_url">
                                <img :src="approvedBooking.qr_code_url" alt="Booking QR Code" class="w-48 h-48 mx-auto object-contain" x-on:error="qrImageFailed = true" x-on:load="qrImageFailed = false">
                            </template>
                            <template x-if="!approvedBooking?.qr_code_url || qrImageFailed">
                                <div class="w-48 h-48 flex items-center justify-center bg-gray-100 rounded-lg">
                                    <div class="text-center">
<i class="w-12 h-12 text-gray-400 mx-auto mb-2 fa-icon fa-solid fa-table-cells text-4xl leading-none"></i>
                                        <p class="text-sm text-gray-500">QR Code</p>
                                        <p class="text-xs text-gray-400">Not available</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <p class="text-xs text-gray-500 mt-3">Scan this QR code to verify the booking</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-3">
                        <button @click="closeSuccessModal()" 
                                class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all">
                            Done
                        </button>
                        <template x-if="approvedBooking?.qr_code_url && !qrImageFailed">
                            <button @click="downloadQr(approvedBooking.qr_code_url, `booking-${approvedBooking.qr_token}.png`)" 
                                    :disabled="isDownloading"
                                    class="px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-medium transition-all flex items-center gap-2 disabled:opacity-50">
                                
                                
                                <span x-text="isDownloading ? 'Saving...' : 'Download'"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Success Modal -->
    <div x-show="showRejectModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-black/30 backdrop-blur-sm transition-opacity"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-2xl transform transition-all"
                 x-show="showRejectModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.stop>
                
                <div class="p-8 text-center">
                    <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
<i class="w-10 h-10 text-red-500 fa-icon fa-solid fa-xmark text-3xl leading-none"></i>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900 mb-2">Booking Rejected</h2>
                    <p class="text-gray-500 text-sm mb-6">The booking request has been rejected.</p>
                    <button @click="closeRejectModal()" 
                            class="w-full px-4 py-3 bg-gray-900 hover:bg-gray-800 text-white rounded-xl font-medium transition-all">
                        Done
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approvalsApp() {
    return {
        showModal: false,
        showSuccessModal: false,
        showRejectModal: false,
        selectedBooking: null,
        approvedBooking: null,
        isLoading: false,
        actionType: null,
        showExceptionInput: false,
        exceptionReason: '',
        qrImageFailed: false,
        isDownloading: false,

        openApprovalModal(booking) {
            this.selectedBooking = booking;
            this.showExceptionInput = false;
            this.exceptionReason = '';
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
            this.selectedBooking = null;
        },

        closeSuccessModal() {
            this.showSuccessModal = false;
            this.approvedBooking = null;
            window.location.reload();
        },

        closeRejectModal() {
            this.showRejectModal = false;
            window.location.reload();
        },

        async approveBooking() {
            if (!this.selectedBooking) return;
            
            this.isLoading = true;
            this.actionType = 'approve';
            
            try {
                const response = await fetch(`/rooms/approvals/${this.selectedBooking.id}/approve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        reason: this.exceptionReason
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    // Prefer server-returned booking (includes qr_token). Build QR image URL if necessary.
                    const booking = data.booking || { ...this.selectedBooking };

                    // If server didn't include qr_code_url, but provided qr_token, compute it
                    if (!booking.qr_code_url && booking.qr_token) {
                        booking.qr_code_url = '/bookings/qr/' + booking.qr_token;
                    }

                    // If server didn't provide qr_token but included qr_code_url, keep it
                    this.approvedBooking = booking;
                    this.qrImageFailed = false; // reset image error state

                    // Close the approval modal and show success modal
                    this.showModal = false;
                    this.showSuccessModal = true;
                } else {
                    alert(data.message || 'Failed to approve booking');
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
                const response = await fetch(`/rooms/approvals/${this.selectedBooking.id}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    this.showModal = false;
                    this.showRejectModal = true;
                } else {
                    alert(data.message || 'Failed to reject booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred');
            } finally {
                this.isLoading = false;
                this.actionType = null;
            }
        },

        // Save QR image locally. Uses File System Access API when available (allows user to
        // pick a folder/path); falls back to a normal browser download.
        async downloadQr(url, filename = 'booking-qr.png') {
            if (!url) return;
            this.isDownloading = true;

            try {
                let blob;

                if (url.startsWith('data:')) {
                    // data URI -> decode
                    const base64 = url.split(',')[1];
                    const binary = atob(base64);
                    const array = new Uint8Array(binary.length);
                    for (let i = 0; i < binary.length; i++) array[i] = binary.charCodeAt(i);
                    blob = new Blob([array], { type: 'image/png' });
                } else {
                    const resp = await fetch(url, { credentials: 'same-origin' });
                    if (!resp.ok) throw new Error('Failed to fetch QR image');
                    blob = await resp.blob();
                }

                // Preferred: File System Access API (Chrome/Edge/Opera)
                if (window.showSaveFilePicker) {
                    const handle = await window.showSaveFilePicker({
                        suggestedName: filename,
                        types: [{ description: 'PNG image', accept: { 'image/png': ['.png'] } }]
                    });
                    const writable = await handle.createWritable();
                    await writable.write(blob);
                    await writable.close();
                } else {
                    // Fallback: standard download
                    const blobUrl = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    URL.revokeObjectURL(blobUrl);
                }
            } catch (err) {
                console.error('Download failed', err);
                alert('Failed to save QR image');
            } finally {
                this.isDownloading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
