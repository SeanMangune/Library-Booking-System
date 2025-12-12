@extends('layouts.app')

@section('title', 'Approvals - Library Booking System')

@section('breadcrumb')
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-500">Rooms</span>
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
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
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['pending'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Pending Reviews</p>
        </div>

        <!-- Approved -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <span class="text-3xl font-bold text-gray-900">{{ $stats['approved'] }}</span>
            </div>
            <p class="mt-3 text-sm font-medium text-gray-600">Approved</p>
        </div>

        <!-- Rejected -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
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
                'room_name' => $booking->room->name,
                'room_location' => $booking->room->location,
                'room_capacity' => $booking->room->capacity,
                'date' => $booking->date->format('M j, Y'),
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
            ];
        @endphp
        <div class="booking-card bg-white rounded-xl border border-gray-200 shadow-sm p-5 hover:shadow-md transition-all cursor-pointer"
             x-on:click="openApprovalModal({{ Js::from($bookingData) }})">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $booking->room->name }}</h3>
                            @if($booking->room->location)
                            <span class="text-sm text-gray-500">{{ $booking->room->location }}</span>
                            @endif
                            
                            @if($booking->has_conflict)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Conflict
                            </span>
                            @endif
                            
                            @if($booking->exceedsCapacity())
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                                Over Capacity
                            </span>
                            @endif
                        </div>
                        
                        @if($booking->title)
                        <p class="text-sm text-gray-700 mt-1">{{ $booking->title }}</p>
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
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
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
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeModal()"></div>
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
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Capacity Warning -->
                    <template x-if="selectedBooking?.exceeds_capacity">
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-xl mb-4">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                                </svg>
                                <span class="text-sm font-semibold text-purple-800">Capacity Exceeded</span>
                            </div>
                            <p class="text-sm text-purple-700 mb-3" x-text="'This booking requests ' + selectedBooking?.attendees + ' attendees but the room capacity is ' + selectedBooking?.room_capacity + '.'"></p>
                            
                            <div x-show="showExceptionInput" class="mb-3">
                                <textarea x-model="exceptionReason"
                                          placeholder="Enter the reason for capacity exception..."
                                          class="w-full p-3 border border-purple-200 rounded-lg text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-purple-300 focus:border-purple-300 resize-none"
                                          rows="3"></textarea>
                            </div>
                            
                            <button x-show="!showExceptionInput" @click="showExceptionInput = true"
                                    class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                Request Exception Reason
                            </button>
                        </div>
                    </template>

                    <!-- Conflict Warning -->
                    <template x-if="selectedBooking?.has_conflict">
                        <div class="flex items-start gap-3 p-4 bg-red-50 border border-red-200 rounded-xl mb-4">
                            <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
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
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Room</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.room_name"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Date</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.date"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Time</p>
                                <p class="text-sm font-semibold text-gray-900" x-text="selectedBooking?.formatted_time || selectedBooking?.time"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl">
                            <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
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
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="text-gray-900" x-text="selectedBooking?.user_name"></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-gray-500" x-text="selectedBooking?.user_email"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendees -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl mb-6">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
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
                                :disabled="isLoading || (selectedBooking?.exceeds_capacity && !showExceptionInput)"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg x-show="!isLoading || actionType !== 'approve'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg x-show="isLoading && actionType === 'approve'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="isLoading && actionType === 'approve' ? 'Approving...' : (showExceptionInput ? 'Approve with Exception' : 'Approve')"></span>
                        </button>
                        <button @click="rejectBooking()"
                                :disabled="isLoading"
                                class="flex items-center justify-center gap-2 px-6 py-3 bg-red-500 hover:bg-red-600 text-white rounded-xl font-medium transition-all disabled:opacity-50">
                            <svg x-show="!isLoading || actionType !== 'reject'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <svg x-show="isLoading && actionType === 'reject'" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="isLoading && actionType === 'reject' ? 'Rejecting...' : 'Reject'"></span>
                        </button>
                    </div>
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
        selectedBooking: null,
        isLoading: false,
        actionType: null,
        showExceptionInput: false,
        exceptionReason: '',

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
                    alert(data.message);
                    window.location.reload();
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
                    alert(data.message);
                    window.location.reload();
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
        }
    }
}
</script>
@endpush
@endsection
