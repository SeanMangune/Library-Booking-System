@extends('layouts.app')

@section('title', 'Reports | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Reports</span>
@endsection

@section('content')
<div class="space-y-6" id="reports-page-top">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between mb-8 animate-slide-in-up stagger-1">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Detailed Reports</h1>
            <p class="text-base text-gray-500 mt-1">Track bookings, approvals, room usage, and collaborative-room exceptions in real-time.</p>
        </div>
        <div class="flex items-center gap-3 print:hidden">
            <button type="button"
                    onclick="window.print()"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-all hover:shadow-sm">
                <i class="w-4 h-4 fa-icon fa-solid fa-print text-base leading-none"></i>
                Print Report
            </button>

            <a href="{{ route('reports.index', array_filter(array_merge($filters, ['export' => 'csv']))) }}"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold transition-all hover:shadow-lg hover:-translate-y-0.5">
                <i class="w-4 h-4 fa-icon fa-solid fa-download text-base leading-none"></i>
                Download CSV
            </a>
        </div>
    </div>

    <!-- Report Navigator -->
    <div class="print:hidden report-nav-shell bg-gradient-to-r from-slate-900 via-indigo-900 to-slate-900 rounded-2xl p-6 text-white border border-slate-800 shadow-lg mb-8 animate-slide-in-up stagger-2" id="report-nav-shell">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.25em] text-indigo-300/80">Premium Analytics</p>
                <h2 class="text-xl font-bold mt-1 tracking-tight">Report Navigator</h2>
                <p class="text-xs text-indigo-200/60 mt-0.5">Jump directly to the data insights you need</p>
            </div>
            <div class="flex flex-wrap gap-2.5">
                <a href="#report-summary" class="report-nav-link inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold hover:bg-white/15 transition-all">Summary</a>
                <a href="#room-breakdown" class="report-nav-link inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold hover:bg-white/15 transition-all">Room Breakdown</a>
                <a href="#top-requesters" class="report-nav-link inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold hover:bg-white/15 transition-all">Top Requesters</a>
                <a href="#daily-activity" class="report-nav-link inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold hover:bg-white/15 transition-all">Daily Activity</a>
                <a href="#detailed-bookings" class="report-nav-link inline-flex items-center rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-bold hover:bg-white/15 transition-all">Detailed List</a>
            </div>
        </div>
    </div>

    <!-- Unified Filter Bar -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 md:p-8 mb-8 report-reveal report-filter-shell animate-slide-in-up stagger-3" id="report-filters">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8 pb-6 border-b border-gray-100">
            <div>
                <h2 class="text-xl font-black text-gray-900 tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-filter text-indigo-500 text-sm"></i> Reports Engine
                </h2>
                <p class="text-sm text-gray-500 mt-1 font-medium">Precision filtering for your library data infrastructure.</p>
            </div>
            <div class="w-full sm:w-72">
                <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-gray-400 mb-2">Smart Presets</label>
                <select id="date-range-select" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-gray-50/50 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-bold text-gray-700 cursor-pointer hover:bg-white">
                    <option value="all">All time</option>
                    <option value="today">Today</option>
                    <option value="7">Last 7 days</option>
                    <option value="30">Last 30 days</option>
                    <option value="month">This month</option>
                    <option value="last_month">Last month</option>
                    <option value="year">This year</option>
                    <option value="custom">Custom range</option>
                </select>
            </div>
        </div>

        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6" id="reports-filter-form">
            <div class="space-y-1.5">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest">Date From</label>
                <input type="text" name="date_from" id="date-from-input" value="{{ $filters['date_from'] }}" placeholder="Pick a date" readonly
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-semibold text-gray-900 cursor-pointer">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest">Date To</label>
                <input type="text" name="date_to" id="date-to-input" value="{{ $filters['date_to'] }}" placeholder="Pick a date" readonly
                       class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-semibold text-gray-900 cursor-pointer">
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest">Room Filter</label>
                <select name="room_id" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-semibold text-gray-900 cursor-pointer">
                    <option value="">All rooms</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected($filters['room_id'] === (string) $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-black text-gray-500 uppercase tracking-widest">Booking Status</label>
                <select name="status" class="w-full px-4 py-3 border border-gray-200 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-semibold text-gray-900 cursor-pointer">
                    <option value="">All statuses</option>
                    @foreach(['approved', 'pending', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-3">
                <a href="{{ route('reports.index') }}" class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 text-xs font-black uppercase tracking-widest transition-all hover:shadow-sm">
                    Reset
                </a>
                <button type="submit" class="flex-[1.5] inline-flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-black uppercase tracking-widest transition-all hover:shadow-indigo-200 hover:shadow-xl hover:-translate-y-0.5">
                    Apply Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6 mb-8" id="report-summary">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Total Bookings</p>
                <i class="fa-solid fa-list-ol text-gray-200 group-hover:text-indigo-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-gray-900 tracking-tight">{{ number_format($stats['total']) }}</p>
            <div class="mt-4 h-1 w-12 bg-indigo-100 group-hover:w-full transition-all duration-500"></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-emerald-400 uppercase tracking-[0.2em]">Approved</p>
                <i class="fa-solid fa-circle-check text-emerald-100 group-hover:text-emerald-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-emerald-600 tracking-tight">{{ number_format($stats['approved']) }}</p>
            <div class="mt-4 h-1 w-12 bg-emerald-100 group-hover:w-full transition-all duration-500"></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-amber-400 uppercase tracking-[0.2em]">Pending</p>
                <i class="fa-solid fa-clock text-amber-100 group-hover:text-amber-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-amber-600 tracking-tight">{{ number_format($stats['pending']) }}</p>
            <div class="mt-4 h-1 w-12 bg-amber-100 group-hover:w-full transition-all duration-500"></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-rose-400 uppercase tracking-[0.2em]">Rejected</p>
                <i class="fa-solid fa-circle-xmark text-rose-100 group-hover:text-rose-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-rose-600 tracking-tight">{{ number_format($stats['rejected']) }}</p>
            <div class="mt-4 h-1 w-12 bg-rose-100 group-hover:w-full transition-all duration-500"></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Cancelled</p>
                <i class="fa-solid fa-ban text-slate-100 group-hover:text-slate-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-slate-600 tracking-tight">{{ number_format($stats['cancelled']) }}</p>
            <div class="mt-4 h-1 w-12 bg-slate-100 group-hover:w-full transition-all duration-500"></div>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 report-reveal transition-all hover:shadow-md group">
            <div class="flex items-center justify-between mb-4">
                <p class="text-xs font-black text-indigo-400 uppercase tracking-[0.2em]">Capacity Requests</p>
                <i class="fa-solid fa-users text-indigo-100 group-hover:text-indigo-200 transition-colors"></i>
            </div>
            <p class="text-4xl font-black text-indigo-600 tracking-tight">{{ number_format($stats['capacity_exceptions']) }}</p>
            <div class="mt-4 h-1 w-12 bg-indigo-100 group-hover:w-full transition-all duration-500"></div>
        </div>
    </div>

    <!-- Data Tables Grid -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal group hover:shadow-lg transition-all" id="room-breakdown">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center text-indigo-600 shadow-inner">
                        <i class="fa-solid fa-door-open"></i>
                    </div>
                    <h2 class="text-lg font-black text-gray-900 tracking-tight">Room Breakdown</h2>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white border-b border-gray-100">
                        <tr>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Room</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Total</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Approved</th>
                            <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Exception</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($roomBreakdown as $row)
                            <tr class="hover:bg-indigo-50/30 transition-colors">
                                <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ $row['room_name'] }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-600">{{ number_format($row['bookings']) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-emerald-600">{{ number_format($row['approved']) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-blue-600">{{ number_format($row['capacity_exceptions']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-sm font-bold text-gray-400 uppercase tracking-widest bg-gray-50/20 italic">No room profile data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal group hover:shadow-lg transition-all" id="top-requesters">
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 shadow-inner">
                        <i class="fa-solid fa- ट्रॉफी"></i>
                    </div>
                    <h2 class="text-lg font-black text-gray-900 tracking-tight">Top Requesters</h2>
                </div>
            </div>
            <div class="divide-y divide-gray-100">
                @forelse($topRequesters as $index => $requester)
                    <div class="px-6 py-5 flex items-center justify-between gap-4 hover:bg-emerald-50/30 transition-all group/item">
                        <div class="flex items-center gap-4">
                            <span class="text-xs font-black text-gray-300 group-hover/item:text-emerald-300 w-4 tracking-tighter">{{ $index + 1 }}</span>
                            <div class="text-sm font-black text-gray-800 tracking-tight group-hover/item:text-emerald-700">{{ $requester['user_name'] }}</div>
                        </div>
                        <div class="text-xs font-bold px-3 py-1.5 rounded-xl bg-gray-100 text-gray-500 group-hover/item:bg-emerald-100 group-hover/item:text-emerald-700 transition-all">{{ number_format($requester['bookings']) }} Bookings</div>
                    </div>
                @empty
                    <div class="px-6 py-12 text-center text-sm font-bold text-gray-400 uppercase tracking-widest bg-gray-50/20 italic">No requester activity records.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Activity Feed Table -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal mb-8 hover:shadow-lg transition-all" id="daily-activity">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-600 shadow-inner">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <h2 class="text-lg font-black text-gray-900 tracking-tight">Temporal Activity Matrix</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Date Cycle</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Volume</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Approved</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Pending</th>
                        <th class="px-6 py-4 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Intensity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($dailyBreakdown as $row)
                        @php
                            $intensity = $stats['total'] > 0 ? ($row['bookings'] / $stats['total']) * 100 : 0;
                        @endphp
                        <tr class="hover:bg-amber-50/30 transition-colors group/row">
                            <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($row['date'])->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-600">{{ number_format($row['bookings']) }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-emerald-600">{{ number_format($row['approved']) }}</td>
                            <td class="px-6 py-4 text-sm font-bold text-amber-600">{{ number_format($row['pending']) }}</td>
                            <td class="px-6 py-4">
                                <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden flex shadow-inner">
                                    <div class="h-full bg-amber-400 group-hover/row:bg-amber-500 transition-all rounded-full" style="width: {{ $intensity }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm font-bold text-gray-400 uppercase tracking-widest bg-gray-50/20 italic">No activity data within specified parameters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Exportable List -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal mb-8 hover:shadow-lg transition-all" id="detailed-bookings">
        <div class="px-6 py-5 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-600 shadow-inner">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h2 class="text-lg font-black text-gray-900 tracking-tight">Granular Raw Data</h2>
            </div>
            <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-100 px-3 py-1 rounded-full">{{ $bookings->total() }} records match</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-white border-b border-gray-100 shadow-sm relative z-10">
                    <tr>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Timestamp</th>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Entity</th>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Objective</th>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Requester</th>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">Scale</th>
                        <th class="px-6 py-5 text-left text-[10px] font-black text-gray-400 uppercase tracking-[0.25em]">System State</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($bookings as $booking)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-[13px] font-bold text-slate-900 tabular-nums">{{ $booking->formatted_date }}</td>
                            <td class="px-6 py-4 text-[13px] font-bold text-slate-600">{{ $booking->room?->name }}</td>
                            <td class="px-6 py-4 text-[13px] font-semibold text-slate-900">
                                <div>{{ $booking->title ?: 'N/A' }}</div>
                                @if($booking->requiresCapacityPermission())
                                    <div class="mt-1.5 inline-flex items-center px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-tighter bg-indigo-50 text-indigo-700 border border-indigo-100">Cap. Override Required</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-[13px] font-bold text-slate-600">{{ $booking->user_name }}</td>
                            <td class="px-6 py-4 text-[13px] font-black text-slate-900">{{ number_format($booking->attendees) }}</td>
                            <td class="px-6 py-4">
                                @php
                                    $tokenClass = match($booking->status) {
                                        'approved' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                        'pending' => 'bg-amber-100 text-amber-800 border-amber-200',
                                        'rejected' => 'bg-rose-100 text-rose-800 border-rose-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="px-3 py-1.5 rounded-xl text-[10px] font-black uppercase border {{ $tokenClass }}">
                                    {{ $booking->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-24 text-center text-sm font-bold text-gray-400 uppercase tracking-widest bg-gray-50/20 italic">No historical data found for current logic.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bookings->hasPages())
            <div class="px-6 py-6 border-t border-gray-100 bg-gray-50/30">
                {{ $bookings->fragment('detailed-bookings')->links() }}
            </div>
        @endif
    </div>
</div>

<div class="print:hidden fixed bottom-8 right-8 z-40 flex flex-col gap-3" id="report-floating-nav">
    <button type="button" id="report-back-top" class="hidden items-center justify-center gap-2 rounded-2xl bg-indigo-600 text-white px-5 py-3.5 text-xs font-black uppercase tracking-widest shadow-2xl hover:bg-indigo-700 transition-all hover:scale-105 active:scale-95 group">
        <i class="fa-solid fa-arrow-up text-sm group-hover:-translate-y-1 transition-transform"></i>
        Ascend to Top
    </button>
</div>

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
(() => {
    const form = document.getElementById('reports-filter-form');
    const fromInput = document.getElementById('date-from-input');
    const toInput = document.getElementById('date-to-input');
    const rangeSelect = document.getElementById('date-range-select');
    const navShell = document.getElementById('report-nav-shell');
    const navLinks = document.querySelectorAll('.report-nav-link');
    const backTopButton = document.getElementById('report-back-top');
    const revealTargets = document.querySelectorAll('.report-reveal');
    let lastScrollY = window.scrollY;

    if (!form || !fromInput || !toInput || !rangeSelect) return;

    /* ── Flatpickr Calendar Date Pickers ── */
    const fpConfig = {
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'M d, Y',
        allowInput: false,
        disableMobile: true,
        animate: true,
    };

    const fpFrom = flatpickr(fromInput, {
        ...fpConfig,
        defaultDate: fromInput.value || null,
        onChange(selectedDates) {
            if (selectedDates[0]) {
                fpTo.set('minDate', selectedDates[0]);
            }
        },
    });

    const fpTo = flatpickr(toInput, {
        ...fpConfig,
        defaultDate: toInput.value || null,
        onChange(selectedDates) {
            if (selectedDates[0]) {
                fpFrom.set('maxDate', selectedDates[0]);
            }
        },
    });

    /* ── Date Helpers ── */
    const toDateString = (value) => {
        const year = value.getFullYear();
        const month = String(value.getMonth() + 1).padStart(2, '0');
        const day = String(value.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    const setRange = (range) => {
        const today = new Date();
        const end = new Date(today);
        let start = new Date(today);

        if (range === 'all') {
            fpFrom.clear();
            fpTo.clear();
            return;
        }

        if (range === 'today') {
            fpFrom.setDate(today, true);
            fpTo.setDate(today, true);
            return;
        }

        if (range === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        } else if (range === 'last_month') {
            const firstDayCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDayPreviousMonth = new Date(firstDayCurrentMonth.getTime() - 86400000);
            const firstDayPreviousMonth = new Date(lastDayPreviousMonth.getFullYear(), lastDayPreviousMonth.getMonth(), 1);
            fpFrom.setDate(firstDayPreviousMonth, true);
            fpTo.setDate(lastDayPreviousMonth, true);
            return;
        } else if (range === 'year') {
            start = new Date(today.getFullYear(), 0, 1);
        } else if (range === 'custom') {
            return;
        } else {
            const days = Number(range);
            start.setDate(start.getDate() - (days - 1));
        }

        fpFrom.setDate(start, true);
        fpTo.setDate(end, true);
    };

    rangeSelect.addEventListener('change', () => setRange(rangeSelect.value || 'all'));

    /* ── Floating Navigation ── */
    const toggleFloatingNav = () => {
        const shouldShow = window.scrollY > 400;
        const isScrollingDown = window.scrollY > lastScrollY;
        
        if (navShell) {
            if (window.scrollY < 200) navShell.classList.remove('is-retracted');
            else navShell.classList.toggle('is-retracted', !isScrollingDown);
        }

        if (backTopButton) {
            backTopButton.classList.toggle('hidden', !shouldShow);
            backTopButton.classList.toggle('inline-flex', shouldShow);
        }
        lastScrollY = window.scrollY;
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href');
            if (!href.startsWith('#')) return;
            const target = document.querySelector(href);
            if (!target) return;
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    if (backTopButton) {
        backTopButton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }

    window.addEventListener('scroll', toggleFloatingNav, { passive: true });

    /* ── Bidirectional Scroll Reveal: fade-in when entering, fade-out when leaving ── */
    if ('IntersectionObserver' in window && revealTargets.length) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                entry.target.classList.toggle('is-visible', entry.isIntersecting);
            });
        }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
        revealTargets.forEach((target) => revealObserver.observe(target));
    }

    /* ── Scroll to anchor on page load if hash is present (pagination) ── */
    if (window.location.hash) {
        const hashTarget = document.querySelector(window.location.hash);
        if (hashTarget) {
            requestAnimationFrame(() => {
                hashTarget.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        }
    }
})();
</script>
@endpush

@push('styles')
<style>
html { scroll-behavior: smooth; }
.report-nav-shell {
    position: sticky;
    top: 1.5rem;
    z-index: 30;
    transition: all 400ms cubic-bezier(0.4, 0, 0.2, 1);
}
.report-nav-shell.is-retracted {
    transform: translateY(-130%) scale(0.95);
    opacity: 0;
    pointer-events: none;
}
.report-filter-shell { position: relative; overflow: hidden; }
.report-filter-shell::after {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.05), transparent 40%);
    pointer-events: none;
}
.report-reveal {
    opacity: 0;
    transform: translateY(24px) scale(0.97);
    filter: blur(3px);
    transition: opacity 600ms cubic-bezier(0.4, 0, 0.2, 1),
                transform 600ms cubic-bezier(0.4, 0, 0.2, 1),
                filter 600ms cubic-bezier(0.4, 0, 0.2, 1);
    will-change: transform, opacity, filter;
}
.report-reveal.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: blur(0);
}
/* Flatpickr overrides for premium look */
.flatpickr-calendar {
    border-radius: 16px !important;
    box-shadow: 0 20px 60px -15px rgba(0,0,0,0.2) !important;
    border: 1px solid #e5e7eb !important;
    font-family: inherit !important;
}
.flatpickr-months .flatpickr-month {
    border-radius: 16px 16px 0 0 !important;
}
.flatpickr-day.selected,
.flatpickr-day.startRange,
.flatpickr-day.endRange {
    background: #6366f1 !important;
    border-color: #6366f1 !important;
}
.flatpickr-day.today {
    border-color: #6366f1 !important;
}
.flatpickr-day:hover {
    background: #eef2ff !important;
    border-color: #c7d2fe !important;
}
</style>
@endpush
@endsection