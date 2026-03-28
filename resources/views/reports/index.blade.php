@extends('layouts.app')

@section('title', 'Reports | SmartSpace')

@section('breadcrumb')
<i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
<span class="text-gray-700 font-medium">Reports</span>
@endsection

@section('content')
<div class="space-y-6" id="reports-page-top">
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

<<<<<<< HEAD
    <div class="print:hidden report-nav-shell bg-gradient-to-r from-slate-900 via-indigo-900 to-slate-900 rounded-2xl p-5 text-white border border-slate-800 shadow-sm" id="report-nav-shell">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-200">Report Navigator</p>
                <h2 class="text-lg font-semibold mt-1">Jump directly to the section you need</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="#report-summary" class="report-nav-link inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20 transition-colors">Summary</a>
                <a href="#room-breakdown" class="report-nav-link inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20 transition-colors">Room Breakdown</a>
                <a href="#top-requesters" class="report-nav-link inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20 transition-colors">Top Requesters</a>
                <a href="#daily-activity" class="report-nav-link inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20 transition-colors">Daily Activity</a>
                <a href="#detailed-bookings" class="report-nav-link inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold hover:bg-white/20 transition-colors">Detailed List</a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 md:p-6 report-reveal report-filter-shell" id="report-filters">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900">Filters</h2>
                <p class="text-sm text-gray-500 mt-0.5">Choose a date range preset, then fine-tune From and To with calendar selectors.</p>
            </div>
            <div class="w-full sm:w-64">
                <label class="block text-xs font-semibold uppercase tracking-wide text-gray-500 mb-1">Date range preset</label>
                <select id="date-range-select" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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

        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-4" id="reports-filter-form">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" id="date-from-input" value="{{ $filters['date_from'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Pick the first day to include.</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" id="date-to-input" value="{{ $filters['date_to'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Pick the last day to include.</p>
=======
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-4 mb-6">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-col gap-4 md:grid md:grid-cols-4 md:gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
>>>>>>> ca25bc025b3782320ae4bd77168d19933eb1ba21
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Room</label>
                <select name="room_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">All rooms</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" @selected($filters['room_id'] === (string) $room->id)>{{ $room->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                    <option value="">All statuses</option>
                    @foreach(['approved', 'pending', 'rejected', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 xl:col-span-1 flex flex-wrap xl:flex-col items-end justify-end gap-2">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold transition-colors">
                    Reset filters
                </a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                    Apply filters
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" id="report-summary">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Total bookings</p>
            <p class="mt-2 text-3xl font-bold text-gray-900">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Approved</p>
            <p class="mt-2 text-3xl font-bold text-emerald-600">{{ $stats['approved'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Pending</p>
            <p class="mt-2 text-3xl font-bold text-amber-600">{{ $stats['pending'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Rejected</p>
            <p class="mt-2 text-3xl font-bold text-rose-600">{{ $stats['rejected'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Cancelled</p>
            <p class="mt-2 text-3xl font-bold text-slate-600">{{ $stats['cancelled'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 report-reveal">
            <p class="text-sm text-gray-500">Capacity-permission requests</p>
            <p class="mt-2 text-3xl font-bold text-blue-600">{{ $stats['capacity_exceptions'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal" id="room-breakdown">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="fa-solid fa-door-open text-indigo-500"></i>
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

        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal" id="top-requesters">
            <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
                <i class="fa-solid fa-user-group text-indigo-500"></i>
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

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal" id="daily-activity">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
            <i class="fa-solid fa-calendar-day text-indigo-500"></i>
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

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden report-reveal" id="detailed-bookings">
        <div class="px-5 py-4 border-b border-gray-200 bg-gray-50 flex items-center gap-2">
            <i class="fa-solid fa-list-check text-indigo-500"></i>
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

<div class="print:hidden fixed bottom-5 right-5 z-40 flex flex-col gap-2" id="report-floating-nav">
    <button type="button" id="report-back-top" class="hidden items-center justify-center gap-2 rounded-xl bg-indigo-600 text-white px-3.5 py-2.5 text-xs font-semibold shadow-lg hover:bg-indigo-700 transition-all">
        <i class="w-3.5 h-3.5 fa-icon fa-solid fa-arrow-up text-sm leading-none"></i>
        Back to top
    </button>
</div>

@push('scripts')
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

    if (!form || !fromInput || !toInput || !rangeSelect) {
        return;
    }

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
            fromInput.value = '';
            toInput.value = '';
            return;
        }

        if (range === 'today') {
            fromInput.value = toDateString(today);
            toInput.value = toDateString(today);
            return;
        }

        if (range === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        } else if (range === 'last_month') {
            const firstDayCurrentMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            const lastDayPreviousMonth = new Date(firstDayCurrentMonth.getTime() - 24 * 60 * 60 * 1000);
            const firstDayPreviousMonth = new Date(lastDayPreviousMonth.getFullYear(), lastDayPreviousMonth.getMonth(), 1);
            fromInput.value = toDateString(firstDayPreviousMonth);
            toInput.value = toDateString(lastDayPreviousMonth);
            return;
        } else if (range === 'year') {
            start = new Date(today.getFullYear(), 0, 1);
        } else if (range === 'custom') {
            if (!fromInput.value && !toInput.value) {
                fromInput.value = toDateString(today);
                toInput.value = toDateString(today);
            }
            return;
        } else {
            const days = Number(range);
            start.setDate(start.getDate() - (days - 1));
        }

        fromInput.value = toDateString(start);
        toInput.value = toDateString(end);
    };

    fromInput.addEventListener('change', () => {
        if (fromInput.value && toInput.value && fromInput.value > toInput.value) {
            toInput.value = fromInput.value;
        }
    });

    toInput.addEventListener('change', () => {
        if (fromInput.value && toInput.value && toInput.value < fromInput.value) {
            fromInput.value = toInput.value;
        }
    });

    rangeSelect.addEventListener('change', () => {
        setRange(rangeSelect.value || 'all');
    });

    if (!fromInput.value && !toInput.value) {
        rangeSelect.value = 'all';
    } else {
        rangeSelect.value = 'custom';
    }

    const toggleFloatingNav = () => {
        const shouldShow = window.scrollY > 260;
        const isScrollingDown = window.scrollY > lastScrollY;

        if (navShell) {
            if (window.scrollY < 120) {
                navShell.classList.remove('is-retracted');
            } else {
                navShell.classList.toggle('is-retracted', !isScrollingDown);
            }
        }

        if (backTopButton) {
            backTopButton.classList.toggle('hidden', !shouldShow);
            backTopButton.classList.toggle('inline-flex', shouldShow);
        }

        lastScrollY = window.scrollY;
    };

    navLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            const href = link.getAttribute('href') || '';
            if (!href.startsWith('#')) {
                return;
            }

            const target = document.querySelector(href);
            if (!target) {
                return;
            }

            event.preventDefault();
            window.history.replaceState(null, '', href);
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            toggleFloatingNav();
        });
    });

    if (backTopButton) {
        backTopButton.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    window.addEventListener('scroll', toggleFloatingNav, { passive: true });
    toggleFloatingNav();

    if ('IntersectionObserver' in window && revealTargets.length) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -36px 0px',
        });

        revealTargets.forEach((target) => revealObserver.observe(target));
    } else {
        revealTargets.forEach((target) => target.classList.add('is-visible'));
    }
})();
</script>
@endpush

@push('styles')
<style>
html {
    scroll-behavior: smooth;
}

.report-nav-link {
    position: relative;
    overflow: hidden;
    transform: translateY(0);
}

.report-nav-shell {
    position: sticky;
    top: 1rem;
    z-index: 20;
    transition: transform 260ms ease, opacity 220ms ease, box-shadow 260ms ease;
}

.report-nav-shell.is-retracted {
    transform: translateY(-110%);
    opacity: 0;
    pointer-events: none;
}

.report-filter-shell {
    position: relative;
    overflow: hidden;
}

.report-filter-shell::before {
    content: '';
    position: absolute;
    inset: 0;
    border-radius: 1rem;
    background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.08), transparent 45%), radial-gradient(circle at bottom left, rgba(15, 23, 42, 0.04), transparent 40%);
    pointer-events: none;
}

.report-nav-link::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, transparent 0%, rgba(255, 255, 255, 0.28) 45%, transparent 100%);
    transform: translateX(-120%);
    transition: transform 340ms ease;
}

.report-nav-link:hover {
    transform: translateY(-1px);
}

.report-nav-link:hover::after {
    transform: translateX(120%);
}

.report-reveal {
    opacity: 0;
    transform: translateY(18px) scale(0.985);
    filter: blur(2px);
    transition: opacity 560ms ease, transform 560ms ease, filter 560ms ease;
    will-change: transform, opacity, filter;
}

.report-reveal.is-visible {
    opacity: 1;
    transform: translateY(0) scale(1);
    filter: blur(0);
}

#report-summary .report-reveal:nth-child(2) { transition-delay: 40ms; }
#report-summary .report-reveal:nth-child(3) { transition-delay: 80ms; }
#report-summary .report-reveal:nth-child(4) { transition-delay: 120ms; }
#report-summary .report-reveal:nth-child(5) { transition-delay: 160ms; }
#report-summary .report-reveal:nth-child(6) { transition-delay: 200ms; }

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

@media (max-width: 768px) {
    .report-nav-shell {
        top: 0.5rem;
    }
}
</style>
@endpush
@endsection