@extends('layouts.app')

@section('title', 'Approvals | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
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
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
           class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 block {{ $status === 'pending' ? 'ring-2 ring-blue-400' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-amber-600 fa-icon fa-solid fa-clock text-2xl leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
        </a>

        <!-- Approved -->
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
           class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 block {{ $status === 'approved' ? 'ring-2 ring-green-400' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-green-600 fa-icon fa-solid fa-circle-check text-2xl leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
        </a>

        <!-- Rejected -->
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"
           class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 block {{ $status === 'rejected' ? 'ring-2 ring-red-400' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                    <i class="w-6 h-6 text-red-500 fa-icon fa-solid fa-circle-xmark text-2xl leading-none"></i>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['rejected'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Rejected</p>
        </a>
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

    <!-- Bookings List -->
    <h2 class="text-xl font-semibold mb-2">
        @if($status === 'approved')
            Approved Bookings
        @elseif($status === 'rejected')
            Rejected Bookings
        @else
            Pending Bookings
        @endif
    </h2>
    <div class="space-y-4">
        @forelse($bookings as $booking)
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
                'qr_token' => $booking->qr_token,
            ];
            $statusColor = [
                'pending' => 'bg-amber-50 text-amber-700 border border-amber-200',
                'approved' => 'bg-green-50 text-green-700 border border-green-200',
                'rejected' => 'bg-red-50 text-red-700 border border-red-200',
            ][$booking->status] ?? 'bg-gray-100 text-gray-700';
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
                        @if($booking->title)
                        <p class="text-sm text-gray-700 mt-1"><span class="font-medium">Purpose:</span> {{ $booking->title }}</p>
                        @endif
                        <p class="text-sm text-gray-500 mt-1">
                            Requested by <span class="font-medium text-gray-700">{{ $booking->user_name }}</span>
                        </p>
                    </div>
                </div>
                @if($booking->status === 'approved')
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Approved
                    </span>
                @elseif($booking->status === 'rejected')
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        Rejected
                    </span>
                @else
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        Pending
                    </span>
                @endif
                
                @if($booking->status === 'approved')
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Approved
                    </span>
                @elseif($booking->status === 'rejected')
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        Rejected
                    </span>
                @else
                    <span class="shrink-0 px-3 py-1.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                        Pending
                    </span>
                @endif
>>>>>>> 970e2da92b451106cc28bc469a23cd126fd97fc2
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
            <h3 class="text-lg font-medium text-gray-900">
                @if($status === 'approved')
                    No approved bookings
                @elseif($status === 'rejected')
                    No rejected bookings
                @else
                    No pending approvals
                @endif
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($status === 'approved')
                    All approved booking requests are shown here.
                @elseif($status === 'rejected')
                    All rejected booking requests are shown here.
                @else
                    All booking requests have been reviewed.
                @endif
            </p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($bookings->hasPages())
    <div class="mt-6">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @endif

    <x-modals.approvals.details />
    <x-modals.approvals.success />
    <x-modals.approvals.reject />
</div>
@endsection