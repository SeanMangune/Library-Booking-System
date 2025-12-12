@extends('layouts.app')

@section('title', 'Reservations - Library Booking System')

@section('breadcrumb')
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-500">Rooms</span>
<svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
</svg>
<span class="text-gray-700 font-medium">Room Reservations</span>
@endsection

@section('content')
<div x-data="reservationsApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">All Reservations</h1>
            <p class="text-sm text-gray-500 mt-1">View and manage all room reservations</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('reservations.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                <select name="room" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Rooms</option>
                    @foreach($rooms as $room)
                    <option value="{{ $room->id }}" {{ request('room') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time Period</label>
                <select name="time_period" onchange="this.form.submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all" {{ request('time_period') == 'all' || !request('time_period') ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ request('time_period') == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="this_week" {{ request('time_period') == 'this_week' ? 'selected' : '' }}>This Week</option>
                    <option value="this_month" {{ request('time_period') == 'this_month' ? 'selected' : '' }}>This Month</option>
                    <option value="past" {{ request('time_period') == 'past' ? 'selected' : '' }}>Past</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search reservations..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>
        </form>
    </div>

    <!-- Reservations Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-sm text-gray-600">Showing {{ $bookings->firstItem() ?? 0 }} to {{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }} results</p>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room / Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Attendees</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($bookings as $booking)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg {{ $booking->status === 'cancelled' ? 'bg-gray-100' : 'bg-indigo-100' }} flex items-center justify-center">
                                    <svg class="w-5 h-5 {{ $booking->status === 'cancelled' ? 'text-gray-500' : 'text-indigo-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $booking->room->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $booking->room->location ?? 'No location' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $booking->title ?: $booking->user_name }}</p>
                            <p class="text-xs text-gray-500">{{ $booking->user_email }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $booking->date->format('M d, Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $booking->formatted_time }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <span class="text-sm text-gray-900">{{ $booking->attendees }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                @if($booking->status === 'pending') bg-amber-100 text-amber-700
                                @elseif($booking->status === 'approved') bg-green-100 text-green-700
                                @elseif($booking->status === 'rejected') bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ ucfirst($booking->status) }}
                            </span>
                        </td>
                        @php
                            $viewData = [
                                'id' => $booking->id,
                                'title' => $booking->title,
                                'room_name' => $booking->room->name,
                                'room_location' => $booking->room->location,
                                'date' => $booking->date->format('M d, Y'),
                                'formatted_time' => $booking->formatted_time,
                                'user_name' => $booking->user_name,
                                'user_email' => $booking->user_email,
                                'attendees' => $booking->attendees,
                                'status' => $booking->status,
                                'description' => $booking->description,
                            ];
                        @endphp
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                <button x-on:click="viewBooking({{ Js::from($viewData) }})"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                @if($booking->status === 'approved' && $booking->date >= today())
                                <button @click="cancelBooking({{ $booking->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Cancel">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <p class="text-sm text-gray-500">No reservations found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($bookings->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $bookings->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- View Booking Modal -->
    <div x-show="showViewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeViewModal()"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl" @click.stop>
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 rounded-t-2xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-white">Reservation Details</h2>
                                <p class="text-indigo-100 text-sm">View booking information</p>
                            </div>
                        </div>
                        <button @click="closeViewModal()" class="text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="p-6">
                    <!-- Status Badge -->
                    <div class="mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-amber-100 text-amber-700': viewBooking?.status === 'pending',
                                  'bg-green-100 text-green-700': viewBooking?.status === 'approved',
                                  'bg-red-100 text-red-700': viewBooking?.status === 'rejected',
                                  'bg-gray-100 text-gray-700': viewBooking?.status === 'cancelled'
                              }"
                              x-text="viewBooking?.status?.charAt(0).toUpperCase() + viewBooking?.status?.slice(1)"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Room</p>
                            <p class="font-semibold text-gray-900" x-text="viewBooking?.room_name"></p>
                            <p class="text-sm text-gray-500" x-text="viewBooking?.room_location || 'No location'"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Date & Time</p>
                            <p class="font-semibold text-gray-900" x-text="viewBooking?.date"></p>
                            <p class="text-sm text-gray-500" x-text="viewBooking?.formatted_time"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Booked By</p>
                            <p class="font-semibold text-gray-900" x-text="viewBooking?.user_name"></p>
                            <p class="text-sm text-gray-500" x-text="viewBooking?.user_email"></p>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Attendees</p>
                            <p class="font-semibold text-gray-900" x-text="viewBooking?.attendees + ' people'"></p>
                        </div>
                    </div>

                    <template x-if="viewBooking?.title">
                        <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Title</p>
                            <p class="text-gray-900" x-text="viewBooking?.title"></p>
                        </div>
                    </template>

                    <template x-if="viewBooking?.description">
                        <div class="mb-4 p-4 bg-gray-50 rounded-xl">
                            <p class="text-xs font-medium text-gray-500 mb-1">Description</p>
                            <p class="text-gray-900" x-text="viewBooking?.description"></p>
                        </div>
                    </template>

                    <div class="flex justify-end">
                        <button @click="closeViewModal()"
                                class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium transition-colors">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function reservationsApp() {
    return {
        showViewModal: false,
        viewBooking: null,

        viewBooking(booking) {
            this.viewBooking = booking;
            this.showViewModal = true;
        },

        closeViewModal() {
            this.showViewModal = false;
            this.viewBooking = null;
        },

        async cancelBooking(id) {
            if (!confirm('Are you sure you want to cancel this booking?')) return;
            
            try {
                const response = await fetch(`/rooms/room-reservations/${id}/cancel`, {
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
                    alert(data.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while cancelling the booking');
            }
        }
    }
}
</script>
@endpush
@endsection
