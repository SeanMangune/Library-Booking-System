@extends('layouts.app')

@section('title', 'Reports | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Reports</span>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Detailed Reports</h1>
            <p class="text-sm text-gray-500 mt-1">Track bookings, approvals, room usage, and collaborative-room exceptions.</p>
        </div>
        <div class="flex items-center gap-2 print:hidden">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors">
                <i class="w-4 h-4 fa-icon fa-solid fa-print text-base leading-none"></i>
                Print Report
            </button>

            <a href="{{ route('reports.index', array_filter(array_merge($filters, ['export' => 'csv']))) }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition-colors">
                <i class="w-4 h-4 fa-icon fa-solid fa-download text-base leading-none"></i>
                Download CSV
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                <select name="room_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All rooms</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected($filters['room_id'] === (string) $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All statuses</option>
                    @foreach(['approved', 'pending', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                    Apply filters
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Total bookings</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Approved</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Rejected</p>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['rejected'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Cancelled</p>
            <p class="mt-2 text-3xl font-bold text-slate-600">{{ $stats['cancelled'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
            <p class="text-sm text-gray-500">Capacity-permission requests</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['capacity_exceptions'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-base font-bold text-gray-900">Room Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bookings</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Permission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($roomBreakdown as $row)
                            <tr>
                                <td class="px-5 py-3 text-sm text-gray-900">{{ $row['room_name'] }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $row['bookings'] }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $row['approved'] }}</td>
                                <td class="px-5 py-3 text-sm text-gray-600">{{ $row['capacity_exceptions'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No report data found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-base font-bold text-gray-900">Top Requesters</h2>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($topRequesters as $requester)
                    <div class="px-5 py-4 flex items-center justify-between gap-4">
                        <div class="text-sm font-semibold text-gray-900">{{ $requester['user_name'] }}</div>
                        <div class="text-sm text-gray-500">{{ $requester['bookings'] }} bookings</div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center text-sm text-gray-500">No requester data available.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-base font-bold text-gray-900">Daily Activity</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Bookings</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Approved</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pending</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($dailyBreakdown as $row)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $row['bookings'] }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $row['approved'] }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $row['pending'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-500">No daily activity found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-base font-bold text-gray-900">Detailed Booking List</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white border-b border-gray-200">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Room</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purpose</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Booked By</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Attendees</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($bookings as $booking)
                        <tr>
                            <td class="px-5 py-3 text-sm text-gray-900">{{ $booking->formatted_date }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $booking->room?->name }}</td>
                            <td class="px-5 py-3 text-sm text-gray-900">
                                <div>{{ $booking->title ?: 'No purpose provided' }}</div>
                                @if($booking->requiresCapacityPermission())
                                    <div class="mt-1 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-700">Needs librarian permission</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $booking->user_name }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ $booking->attendees }}</td>
                            <td class="px-5 py-3 text-sm text-gray-600">{{ ucfirst($booking->status) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">No bookings found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="px-5 py-4 border-t border-gray-200">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</div>

@push('styles')
<style>
@media print {
    .print\:hidden {
        display: none !important;
    }

    body {
        background: #fff !important;
    }

    .shadow-sm,
    .shadow-xl,
    .rounded-2xl {
        box-shadow: none !important;
    }
}
</style>
@endpush
@endsection