@extends('layouts.app')

@section('title', 'Approvals | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Approvals</span>
@endsection

@section('content')
<div x-data="{
    ...approvalsApp(),
    activeTab: null,
    showList(type) { this.activeTab = this.activeTab === type ? null : type; },
}">
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
           <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 cursor-pointer hover:shadow-md transition-all"
               @click="showList('pending')">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-amber-600 fa-icon fa-solid fa-clock text-2xl leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
        </div>

        <!-- Approved (Clickable) -->
           <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 cursor-pointer hover:shadow-md transition-all"
               @click="showList('approved')">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-green-600 fa-icon fa-solid fa-circle-check text-2xl leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
        </div>

        <!-- Rejected -->
           <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 cursor-pointer hover:shadow-md transition-all"
               @click="showList('rejected')">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-red-500 fa-icon fa-solid fa-circle-xmark text-2xl leading-none"></i>
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

    <!-- Pending Bookings List -->
    <div x-show="activeTab === 'pending'" class="space-y-4">
        @forelse($pendingBookings as $booking)
        @php
            $bookingData = [
                'id' => $booking->id,
                'title' => $booking->title,
                'purpose' => $booking->title,
                'room_name' => $booking->room->name,
                'room_location' => $booking->room->location,
                'room_capacity' => $booking->room->standardBookingCapacityLimit(),
                'date' => $booking->date->format('M j, Y'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'time' => $booking->time,
                'duration' => $booking->duration,
                'user_name' => $booking->user_name,
                'user_email' => $booking->user_email,
                'attendees' => $booking->attendees,
                'status' => $booking->status,
                'booking_status' => $booking->booking_status,
                'description' => $booking->description,
                'has_conflict' => $booking->has_conflict,
                'exceeds_capacity' => $booking->exceedsCapacity(),
                'requires_capacity_permission' => $booking->requiresCapacityPermission(),
                'standard_capacity_limit' => $booking->room->standardBookingCapacityLimit(),
                'student_capacity_limit' => $booking->room->maxStudentBookingCapacity(),
                'qr_code_url' => $booking->qr_code_url,
                'qr_code_data' => $booking->qr_code_data,
            ];
        @endphp
        <div class="booking-card bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer"
             x-on:click="openApprovalModal({{ Js::from($bookingData) }})">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="w-6 h-6 text-indigo-600 fa-icon fa-solid fa-building text-2xl leading-none"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm text-gray-500">{{ $booking->room->location }}</span>
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
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $booking->attendees }} / {{ $booking->room->standardBookingCapacityLimit() }}
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
                <i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No pending approvals</h3>
            <p class="mt-1 text-sm text-gray-500">All booking requests have been reviewed.</p>
        </div>
        @endforelse
    </div>

    <!-- Approved Bookings List -->
    <div x-show="activeTab === 'approved'" class="space-y-4">
        @forelse($approvedBookings as $booking)
        @php
            $bookingData = [
                'id' => $booking->id,
                'title' => $booking->title,
                'purpose' => $booking->title,
                'room_name' => $booking->room->name,
                'room_location' => $booking->room->location,
                'room_capacity' => $booking->room->standardBookingCapacityLimit(),
                'date' => $booking->date->format('M j, Y'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'time' => $booking->time,
                'duration' => $booking->duration,
                'user_name' => $booking->user_name,
                'user_email' => $booking->user_email,
                'attendees' => $booking->attendees,
                'status' => $booking->status,
                'booking_status' => $booking->booking_status,
                'description' => $booking->description,
                'has_conflict' => $booking->has_conflict,
                'exceeds_capacity' => $booking->exceedsCapacity(),
                'requires_capacity_permission' => $booking->requiresCapacityPermission(),
                'standard_capacity_limit' => $booking->room->standardBookingCapacityLimit(),
                'student_capacity_limit' => $booking->room->maxStudentBookingCapacity(),
                'qr_code_url' => $booking->qr_code_url,
                'qr_code_data' => $booking->qr_code_data,
            ];
        @endphp
        <div class="booking-card bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer"
             x-on:click="openApprovalModal({{ Js::from($bookingData) }})">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="w-6 h-6 text-indigo-600 fa-icon fa-solid fa-building text-2xl leading-none"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm text-gray-500">{{ $booking->room->location }}</span>
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
                <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                    Approved
                </span>
            </div>
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
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $booking->attendees }} / {{ $booking->room->standardBookingCapacityLimit() }}
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
                <i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No approved bookings</h3>
            <p class="mt-1 text-sm text-gray-500">No bookings have been approved yet.</p>
        </div>
        @endforelse
    </div>


    <!-- Rejected Bookings List -->
    <div x-show="activeTab === 'rejected'" class="space-y-4">
        @forelse($rejectedBookings as $booking)
        @php
            $bookingData = [
                'id' => $booking->id,
                'title' => $booking->title,
                'purpose' => $booking->title,
                'room_name' => $booking->room->name,
                'room_location' => $booking->room->location,
                'room_capacity' => $booking->room->standardBookingCapacityLimit(),
                'date' => $booking->date->format('M j, Y'),
                'formatted_date' => $booking->formatted_date,
                'formatted_time' => $booking->formatted_time,
                'time' => $booking->time,
                'duration' => $booking->duration,
                'user_name' => $booking->user_name,
                'user_email' => $booking->user_email,
                'attendees' => $booking->attendees,
                'status' => $booking->status,
                'booking_status' => $booking->booking_status,
                'description' => $booking->description,
                'has_conflict' => $booking->has_conflict,
                'exceeds_capacity' => $booking->exceedsCapacity(),
                'requires_capacity_permission' => $booking->requiresCapacityPermission(),
                'standard_capacity_limit' => $booking->room->standardBookingCapacityLimit(),
                'student_capacity_limit' => $booking->room->maxStudentBookingCapacity(),
                'qr_code_url' => $booking->qr_code_url,
                'qr_code_data' => $booking->qr_code_data,
            ];
        @endphp
        <div class="booking-card bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer"
             x-on:click="openApprovalModal({{ Js::from($bookingData) }})">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <i class="w-6 h-6 text-indigo-600 fa-icon fa-solid fa-building text-2xl leading-none"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm text-gray-500">{{ $booking->room->location }}</span>
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
                <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                    Rejected
                </span>
            </div>
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
                    <p class="mt-1 text-sm font-semibold text-gray-900">
                        {{ $booking->attendees }} / {{ $booking->room->standardBookingCapacityLimit() }}
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
                <i class="w-8 h-8 text-gray-400 fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900">No rejected bookings</h3>
            <p class="mt-1 text-sm text-gray-500">No bookings have been rejected yet.</p>
        </div>
        @endforelse
    </div>

    <x-modals.approvals.details />
    <x-modals.approvals.success />
    <x-modals.approvals.reject />
</div>
@endsection