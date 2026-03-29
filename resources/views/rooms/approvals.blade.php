@extends('layouts.app')

@section('title', 'Approvals | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-500">Rooms</span>
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Approvals</span>
@endsection

@section('content')
<div x-data="approvalsApp()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Booking Approvals</h1>
            <p class="text-base text-gray-500 mt-1">Review and manage pending booking requests with real-time updates.</p>
        </div>
    </div>

    <!-- Stats Cards - Pill-style tab switcher -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
        <!-- Pending -->
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'pending'])) }}"
           class="group bg-white rounded-2xl border border-gray-200 shadow-sm p-6 block transition-all duration-300 hover:shadow-md hover:-translate-y-1 {{ $status === 'pending' ? 'ring-2 ring-amber-400 bg-amber-50/30' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-14 h-14 rounded-2xl bg-amber-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <i class="w-7 h-7 text-amber-600 fa-icon fa-solid fa-clock text-3xl leading-none"></i>
                </div>
                <span class="text-4xl font-black text-gray-900">{{ $stats['pending'] }}</span>
            </div>
            <p class="mt-4 text-sm font-bold text-gray-600 uppercase tracking-wider">Pending Reviews</p>
        </a>

        <!-- Approved -->
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'approved'])) }}"
           class="group bg-white rounded-2xl border border-gray-200 shadow-sm p-6 block transition-all duration-300 hover:shadow-md hover:-translate-y-1 {{ $status === 'approved' ? 'ring-2 ring-green-400 bg-green-50/30' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-14 h-14 rounded-2xl bg-green-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <i class="w-7 h-7 text-green-600 fa-icon fa-solid fa-circle-check text-3xl leading-none"></i>
                </div>
                <span class="text-4xl font-black text-gray-900">{{ $stats['approved'] }}</span>
            </div>
            <p class="mt-4 text-sm font-bold text-gray-600 uppercase tracking-wider">Approved</p>
        </a>

        <!-- Rejected -->
        <a href="{{ route('approvals.index', array_merge(request()->except('page'), ['status' => 'rejected'])) }}"
           class="group bg-white rounded-2xl border border-gray-200 shadow-sm p-6 block transition-all duration-300 hover:shadow-md hover:-translate-y-1 {{ $status === 'rejected' ? 'ring-2 ring-red-400 bg-red-50/30' : '' }}">
            <div class="flex items-center justify-between">
                <div class="w-14 h-14 rounded-2xl bg-red-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                    <i class="w-7 h-7 text-red-500 fa-icon fa-solid fa-circle-xmark text-3xl leading-none"></i>
                </div>
                <span class="text-4xl font-black text-gray-900">{{ $stats['rejected'] }}</span>
            </div>
            <p class="mt-4 text-sm font-bold text-gray-600 uppercase tracking-wider">Rejected</p>
        </a>
    </div>

    <!-- Filter by Room -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <form method="GET" action="{{ route('approvals.index') }}" class="flex items-center gap-4 flex-1">
            <label class="text-sm font-bold text-gray-700 whitespace-nowrap uppercase tracking-tight">Filter by Room:</label>
            <select name="room" onchange="this.form.submit()"
                    class="w-full sm:w-64 px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50/50 hover:bg-white cursor-pointer">
                <option value="all">All Rooms</option>
                @foreach($rooms as $room)
                <option value="{{ $room->id }}" {{ request('room') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Bookings List -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            @if($status === 'approved')
                <i class="fa-solid fa-circle-check text-green-500"></i> Approved Bookings
            @elseif($status === 'rejected')
                <i class="fa-solid fa-circle-xmark text-red-500"></i> Rejected Bookings
            @else
                <i class="fa-solid fa-clock text-amber-500"></i> Pending Requests
            @endif
            <span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 ml-1">{{ $bookings->total() }} total</span>
        </h2>
    </div>

    <!-- Content with smooth entrance -->
    <div class="space-y-5">
        @forelse($bookings as $index => $booking)
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
                'qr_code_encrypted' => $booking->qr_code_encrypted ?? null,
                'qr_token' => $booking->qr_token ?? null,
            ];
        @endphp
        <div class="booking-card bg-white rounded-2xl border border-gray-200 shadow-sm p-6 hover:shadow-xl hover:border-indigo-200 hover:-translate-y-1 transition-all duration-300 cursor-pointer group relative"
             style="animation: approvalCardIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) {{ $index * 0.08 }}s both;"
             data-booking="{{ json_encode($bookingData) }}"
             x-on:click="openApprovalModal(JSON.parse($el.dataset.booking))">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-6">
                <div class="flex items-start gap-5 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center shrink-0 group-hover:bg-indigo-600 transition-colors duration-300">
                        <i class="w-7 h-7 text-indigo-600 fa-icon fa-solid fa-building text-3xl leading-none group-hover:text-white transition-colors duration-300"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap mb-2">
                            <h3 class="text-xl font-bold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg">{{ $booking->room->location }}</span>
                            @endif
                        </div>
                        
                        <div class="flex flex-wrap gap-2 mb-3">
                            @if($booking->has_conflict)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-black uppercase bg-red-50 text-red-700 border border-red-200 animate-pulse">
                                <i class="fa-solid fa-triangle-exclamation"></i> Conflict
                            </span>
                            @endif
                            @if($booking->exceedsCapacity())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-black uppercase bg-purple-50 text-purple-700 border border-purple-200">
                                <i class="fa-solid fa-users"></i> Over Capacity
                            </span>
                            @endif
                            @if($booking->requiresCapacityPermission())
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-[11px] font-black uppercase bg-blue-50 text-blue-700 border border-blue-200">
                                <i class="fa-solid fa-shield-halved"></i> Capacity Exception
                            </span>
                            @endif
                        </div>

                        @if($booking->title)
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-bold text-gray-400 tracking-widest uppercase">Purpose</span>
                            <div class="h-[1px] flex-1 bg-gray-100"></div>
                        </div>
                        <p class="text-sm font-semibold text-gray-800 mb-3">{{ $booking->title }}</p>
                        @endif
                        <p class="text-sm text-gray-500">
                            Requested by <span class="font-bold text-gray-900">{{ $booking->user_name }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-3 shrink-0">
                    @if($booking->status === 'approved')
                        <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 border-2 border-emerald-200 shadow-sm">
                            Approved
                        </span>
                    @elseif($booking->status === 'rejected')
                        <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-800 border-2 border-rose-200 shadow-sm">
                            Rejected
                        </span>
                    @else
                        <span class="px-4 py-2 rounded-xl text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-800 border-2 border-amber-200 shadow-sm flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-600 animate-ping"></span>
                            Pending Review
                        </span>
                    @endif
                    <div class="bg-gray-50 group-hover:bg-indigo-50 px-4 py-2 rounded-xl transition-colors duration-300">
                        <span class="text-[10px] font-bold text-gray-400 uppercase block tracking-tighter">Status Tag</span>
                        <span class="text-xs font-black text-gray-600 group-hover:text-indigo-700 uppercase">{{ $booking->booking_status ?: 'General' }}</span>
                    </div>
                </div>
            </div>

            <!-- Detail Grid Premium -->
            <div class="mt-6 pt-6 border-t border-gray-100 grid grid-cols-2 lg:grid-cols-4 gap-6 relative transition-all duration-300 group-hover:border-indigo-100">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover:text-indigo-400 transition-colors">Date</span>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-calendar-alt text-gray-300 group-hover:text-indigo-300"></i>
                        <span class="text-sm font-bold text-gray-900">{{ $booking->date->format('M j, Y') }}</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover:text-indigo-400 transition-colors">Time Range</span>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock text-gray-300 group-hover:text-indigo-300"></i>
                        <span class="text-sm font-bold text-gray-900">{{ $booking->formatted_time }}</span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover:text-indigo-400 transition-colors">Attendees</span>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-users text-gray-300 group-hover:text-indigo-300"></i>
                        <span class="text-sm font-bold {{ $booking->exceedsCapacity() ? 'text-purple-600 underline decoration-purple-200 decoration-2' : 'text-gray-900' }}">
                            {{ $booking->attendees }} / {{ $booking->room->standardBookingCapacityLimit() }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover:text-indigo-400 transition-colors">Duration</span>
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-stopwatch text-gray-300 group-hover:text-indigo-300"></i>
                        <span class="text-sm font-bold text-gray-900">{{ $booking->duration ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            <!-- Absolute overlay for hover hint -->
            <div class="absolute bottom-4 right-6 opacity-0 group-hover:opacity-100 transition-all duration-300 translate-y-2 group-hover:translate-y-0 flex items-center gap-2 text-[10px] font-black text-indigo-600 uppercase tracking-tighter">
                Click to view details <i class="fa-solid fa-arrow-right"></i>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-3xl border-2 border-dashed border-gray-200 p-16 text-center">
            <div class="w-20 h-20 mx-auto bg-gray-50 rounded-full flex items-center justify-center mb-6">
                <i class="fa-solid fa-inbox text-gray-300 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-black text-gray-900 mb-2 whitespace-nowrap">
                @if($status === 'approved')
                    All Good! No approved bookings
                @elseif($status === 'rejected')
                    No rejected bookings
                @else
                    Inbox Zero! No pending approvals
                @endif
            </h3>
            <p class="text-base text-gray-500 max-w-sm mx-auto">
                {{ $status === 'pending' ? 'All requests have been reviewed. Take a break!' : 'No data found for the current filter criteria.' }}
            </p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($bookings->hasPages())
    <div class="mt-10">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @endif

    <x-modals.approvals.details />
    <x-modals.approvals.success />
    <x-modals.approvals.reject />
</div>

@push('styles')
<style>
/* Smooth, dynamic card entrance */
@keyframes approvalCardIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
</style>
@endpush
@endsection