@extends('layouts.app')

@section('title', 'Reservations | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">{{ (auth()->user()?->isAdmin() || auth()->user()?->isSuperAdmin()) ? 'All Reservations' : 'My Reservations' }}</span>
@endsection

@section('content')
@push('styles')
    <link rel="stylesheet" href="/vendor/flatpickr/flatpickr.min.css">
@endpush
@push('scripts')
    <script src="/vendor/flatpickr/flatpickr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.querySelector('.flatpickr-date')) {
                flatpickr('.flatpickr-date', {
                    dateFormat: 'Y-m-d',
                    minDate: 'today',
                });
            }
            if (document.querySelector('.js-flatpickr-time')) {
                flatpickr('.js-flatpickr-time', {
                    enableTime: true,
                    noCalendar: true,
                    dateFormat: 'h:i K',
                    time_24hr: false,
                    minuteIncrement: 15,
                });
            }
        });
    </script>
@endpush
@php
    $currentUser = auth()->user();
    $canViewAllReservations = $currentUser?->isAdmin() || $currentUser?->isSuperAdmin();
@endphp
<div x-data="reservationsApp()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <!-- Header Banner -->
        <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl border border-indigo-500/20 shadow-lg p-6 sm:p-8 relative overflow-hidden group/header w-full">
            <div class="absolute -right-4 -bottom-4 opacity-20 transform rotate-12 group-hover/header:scale-110 transition-transform duration-500 pointer-events-none">
                <i class="fa-solid fa-calendar-days text-9xl text-white"></i>
            </div>
            <div class="relative z-10 w-full flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-white tracking-tight">{{ $canViewAllReservations ? 'All Reservations' : 'My Reservations' }}</h1>
                    <p class="text-indigo-100 mt-2 text-base">{{ $canViewAllReservations ? 'View and manage all room reservations' : 'View and manage your room reservations' }}</p>
                </div>
                <!-- Action Buttons could go here if needed -->
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('reservations.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
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
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <input type="text" name="date" value="{{ request('date') }}" placeholder="Select date" class="flatpickr-date w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                <input type="text" name="time" value="{{ request('time') }}" placeholder="Select time" class="js-flatpickr-time w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" autocomplete="off">
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
                    <i class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 fa-icon fa-solid fa-magnifying-glass text-base leading-none"></i>
                </div>
            </div>
        </form>
    </div>

    <!-- Reservations -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <p class="text-sm text-gray-600">Showing {{ $bookings->firstItem() ?? 0 }} to {{ $bookings->lastItem() ?? 0 }} of {{ $bookings->total() }} results</p>
        </div>
        
        <!-- Desktop Table (hidden on mobile) -->
        <div class="hidden sm:block overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Room / Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Purpose</th>
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
                                    <i class="w-5 h-5 {{ $booking->status === 'cancelled' ? 'text-gray-500' : 'text-indigo-600' }} fa-icon fa-solid fa-building text-xl leading-none"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $booking->room->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $booking->room->location ?? 'No location' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900" title="{{ $booking->title ?: $booking->user_name }}">{{ Str::limit($booking->title ?: $booking->user_name, 14) }}</p>
                            <p class="text-xs text-gray-500" title="{{ $booking->user_email }}">{{ Str::limit($booking->user_email, 14) }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-sm font-medium text-gray-900">{{ $booking->date->format('M d, Y') }}</p>
                            <p class="text-xs text-gray-500">{{ $booking->formatted_time }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                <i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-users text-base leading-none"></i>
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
                                'formatted_date' => $booking->formatted_date,
                                'formatted_time' => $booking->formatted_time,
                                'user_name' => $booking->user_name,
                                'user_email' => $booking->user_email,
                                'attendees' => $booking->attendees,
                                'status' => $booking->status,
                                'description' => $booking->description,
                                'booking_status' => $booking->booking_status ?? $booking->determineBookingStatus(),
                                'qr_token' => $booking->qr_token,
                                'qr_code_url' => $booking->qr_code_url,
                            ];
                        @endphp
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-1">
                                <button data-booking="{{ json_encode($viewData) }}"
                                        x-on:click="viewBooking(JSON.parse($el.dataset.booking))"
                                        class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-eye text-base leading-none"></i>
                                </button>
                                @if($booking->status === 'approved' && $booking->date >= today())
                                <button @click="cancelBooking({{ $booking->id }})"
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Cancel">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-xmark text-base leading-none"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i class="w-12 h-12 text-gray-300 mx-auto mb-3 fa-icon fa-solid fa-calendar-days text-5xl leading-none"></i>
                            <p class="text-sm text-gray-500">No reservations found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Mobile Card View (visible only on mobile) -->
        <div class="sm:hidden divide-y divide-gray-100">
            @forelse($bookings as $booking)
            @php
                $viewData = [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'room_name' => $booking->room->name,
                    'room_location' => $booking->room->location,
                    'date' => $booking->date->format('M d, Y'),
                    'formatted_date' => $booking->formatted_date,
                    'formatted_time' => $booking->formatted_time,
                    'user_name' => $booking->user_name,
                    'user_email' => $booking->user_email,
                    'attendees' => $booking->attendees,
                    'status' => $booking->status,
                    'description' => $booking->description,
                    'booking_status' => $booking->booking_status ?? $booking->determineBookingStatus(),
                    'qr_token' => $booking->qr_token,
                    'qr_code_url' => $booking->qr_code_url,
                ];
            @endphp
            <div class="p-4 hover:bg-gray-50 transition-colors cursor-pointer active:bg-gray-100"
                 data-booking="{{ json_encode($viewData) }}"
                 x-on:click="viewBooking(JSON.parse($el.dataset.booking))">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-lg {{ $booking->status === 'cancelled' ? 'bg-gray-100' : 'bg-indigo-100' }} flex items-center justify-center shrink-0">
                            <i class="{{ $booking->status === 'cancelled' ? 'text-gray-500' : 'text-indigo-600' }} fa-solid fa-building"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="font-semibold text-gray-900 truncate">{{ $booking->room->name }}</p>
                            <p class="text-xs text-gray-500">{{ $booking->room->location ?? '' }}</p>
                        </div>
                    </div>
                    <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold
                        @if($booking->status === 'pending') bg-amber-100 text-amber-700
                        @elseif($booking->status === 'approved') bg-green-100 text-green-700
                        @elseif($booking->status === 'rejected') bg-red-100 text-red-700
                        @else bg-gray-100 text-gray-700 @endif">
                        {{ ucfirst($booking->status) }}
                    </span>
                </div>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1">
                        <i class="fa-regular fa-calendar"></i>
                        {{ $booking->date->format('M d, Y') }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fa-regular fa-clock"></i>
                        {{ $booking->formatted_time }}
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fa-solid fa-users"></i>
                        {{ $booking->attendees }}
                    </span>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <i class="fa-solid fa-calendar-days text-gray-300 text-4xl mb-3"></i>
                <p class="text-sm text-gray-500">No reservations found</p>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($bookings->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $bookings->withQueryString()->links() }}
        </div>
        @endif
    </div>

    <!-- View Booking Modal -->
    <x-modals.reservations.view-booking />
</div>

@push('scripts')
<script>
function reservationsApp() {
    return {
        showViewModal: false,
        selectedBooking: null,

        viewBooking(booking) {
            this.selectedBooking = booking;
            this.showViewModal = true;
        },

        closeViewModal() {
            this.showViewModal = false;
            this.selectedBooking = null;
        },

        async cancelBooking(id) {
            const isConfirmed = typeof window.confirmApp === 'function'
                ? await window.confirmApp('Are you sure you want to cancel this booking?', {
                    title: 'Cancel booking?',
                    confirmText: 'Yes, cancel',
                    cancelText: 'Keep booking',
                })
                : false;

            if (!isConfirmed) return;
            
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
                    window.notifyApp?.('success', data.message || 'Booking cancelled successfully.');
                    window.setTimeout(() => {
                        window.location.reload();
                    }, 850);
                } else {
                    window.notifyApp?.('error', data.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error:', error);
                window.notifyApp?.('error', 'An error occurred while cancelling the booking');
            }
        }
    }
}
</script>
@endpush
@endsection
