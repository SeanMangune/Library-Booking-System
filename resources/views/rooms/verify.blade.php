@extends('layouts.guest')

@section('title', 'Verify Booking')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    @if($booking === 'master_unlock')
    <div class="max-w-xl mx-auto text-center py-16 bg-white shadow-2xl rounded-3xl overflow-hidden border border-indigo-100 relative">
        <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-br from-indigo-600 to-indigo-900 rounded-b-[40px] shadow-inner"></div>
        <div class="relative z-10">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-white shadow-xl mb-6 relative border-4 border-indigo-50">
                <i class="w-10 h-10 text-indigo-600 fa-icon fa-solid fa-unlock-keyhole text-4xl leading-none"></i>
            </div>
            <h2 class="text-3xl font-black text-gray-900 tracking-tight">Master Access Granted</h2>
            <p class="text-indigo-600 font-bold tracking-widest uppercase text-xs mt-2">Emergency Override</p>
            <div class="mt-8 px-8">
                <p class="text-gray-600">The master token has successfully authorized this scan. You may proceed to unlock or access the requested resource.</p>
            </div>
            <div class="mt-8">
                <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 transition shadow-lg shadow-indigo-600/30 text-white rounded-xl font-semibold">Back to Dashboard</a>
            </div>
        </div>
    </div>
    @elseif($booking)
    @php
        $lifecycleStatus = $booking->booking_status ?? 'upcoming';
        $approvalStatus = $booking->status ?? 'unknown';
        [$badgeBackground, $badgeText] = match ($lifecycleStatus) {
            'valid' => ['#ECFDF5', '#047857'],
            'expired' => ['#FEF2F2', '#B91C1C'],
            default => ['#FFFBEB', '#B45309'],
        };
    @endphp
    <div class="bg-white shadow rounded-2xl overflow-hidden border border-gray-100">
        <div class="verify-header">
    <div class="verify-left">
        <div class="verify-icon-box">
            <i class="verify-icon fa-icon fa-solid fa-circle-check text-[1.25rem] leading-none"></i>
        </div>

        <div>
            <h1 class="verify-title">Booking verified</h1>
            <p class="verify-subtitle">
                Token: <span class="verify-token">{{ $token }}</span>
            </p>
        </div>
    </div>

    <div>
        @php $badgeStyle = 'background: ' . $badgeBackground . '; color: ' . $badgeText . ';'; @endphp
        {!! '<span class="status-badge" style="' . $badgeStyle . '">' . ucfirst($lifecycleStatus) . '</span>' !!}
    </div>
</div>

<style>
/* Main header container */
.verify-header{
    background: linear-gradient(to right, #059669, #14b8a6); /* emerald-600 → teal-500 */
    padding: 1.25rem 1.5rem; /* px-6 py-5 */
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Left flex group */
.verify-left{
    display: flex;
    align-items: center;
    gap: 1rem; /* gap-4 */
}

/* Icon box */
.verify-icon-box{
    width: 40px;   /* w-10 */
    height: 40px;
    background: rgba(255,255,255,0.2); /* bg-white/20 */
    border-radius: 8px; /* rounded-lg */
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Icon */
.verify-icon{
    width: 20px; /* w-5 */
    height: 20px;
    color: #ffffff;
}

/* Title */
.verify-title{
    font-size: 1.125rem; /* text-lg */
    font-weight: 600;    /* font-semibold */
    margin: 0;
}

/* Subtitle */
.verify-subtitle{
    font-size: 0.875rem; /* text-sm */
    color: rgba(255,255,255,0.8); /* text-white/80 */
    margin: 0;
}

/* Token monospace */
.verify-token{
    font-family: monospace;
}

/* Status badge */
.status-badge{
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem; /* px-3 py-1.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.875rem;
    font-weight: 600;
    background: #ecfdf5;
    color: #065f46;
}
</style>


        <div class="p-6 sm:p-8">
            <div class="grid grid-cols-1 gap-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Room</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $booking->room?->name ?? '—' }}</p>
                        @if($booking->room?->location)
                            <p class="text-sm text-gray-500 mt-1">{{ $booking->room->location }}</p>
                        @endif
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Date</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $booking->formatted_date ?? ($booking->date?->format('M j, Y') ?? '—') }}</p>
                        <p class="text-xs text-gray-400 mt-1">Timezone: {{ config('app.timezone') }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Time</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $booking->formatted_time ?? ($booking->start_time . ' - ' . $booking->end_time) }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Attendees</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $booking->attendees ?? '—' }} people</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2 p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Booked by</p>
                        <p class="mt-1 font-semibold text-gray-900">{{ $booking->user_name ?? '—' }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $booking->user_email ?? '' }}</p>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">QR Status</p>
                        <div class="mt-2">
                            @php $status = $booking->booking_status ?? 'upcoming'; @endphp
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                                                @php
                                                                        $qrBg = $status === 'valid' ? '#ECFDF5' : ($status === 'expired' ? '#FEF2F2' : '#FFFBEB');
                                                                        $qrColor = $status === 'valid' ? '#047857' : ($status === 'expired' ? '#B91C1C' : '#B45309');
                                                                        $qrStyle = 'background: ' . $qrBg . '; color: ' . $qrColor . ';';
                                                                @endphp
                                                                {!! '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold" style="' . $qrStyle . '">' . ucfirst($status) . '</span>' !!}
                            </span>
                            <p class="mt-2 text-xs text-gray-500">Approval: {{ ucfirst($approvalStatus) }}</p>
                        </div>
                    </div>
                </div>

                @if($booking->booking_code || $booking->duration || $booking->description)
                <div class="space-y-3">
                    @if($booking->booking_code)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Booking code</p>
                            <p class="font-mono font-semibold text-gray-900 mt-1">{{ $booking->booking_code }}</p>
                        </div>
                        <button class="text-sm text-gray-600 hover:text-gray-900" onclick="navigator.clipboard?.writeText('{{ $booking->booking_code }}')">Copy</button>
                    </div>
                    @endif

                    @if($booking->duration)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Duration</p>
                        <p class="font-semibold text-gray-900 mt-1">{{ $booking->duration }}</p>
                    </div>
                    @endif

                    @if($booking->description)
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Notes</p>
                        <p class="text-gray-700 mt-1">{{ $booking->description }}</p>
                    </div>
                    @endif
                </div>
                @endif

                <div class="pt-4">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">Back to dashboard</a>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="max-w-xl mx-auto text-center py-20">
        <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-50 mb-6">
            <i class="w-8 h-8 text-red-600 fa-icon fa-solid fa-xmark text-3xl leading-none"></i>
        </div>
        <h2 class="text-2xl font-semibold">Invalid or expired token</h2>
        <p class="text-gray-500 mt-2">We couldn't find a booking for token <code>{{ $token }}</code>. The link may be incorrect or expired.</p>
        <div class="mt-6">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white rounded-lg text-sm">Back to dashboard</a>
        </div>
    </div>
    @endif
</div>

/* Main header container */
.verify-header{
    background: linear-gradient(to right, #059669, #14b8a6); /* emerald-600 → teal-500 */
    padding: 1.25rem 1.5rem; /* px-6 py-5 */
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* Left flex group */
.verify-left{
    display: flex;
    align-items: center;
    gap: 1rem; /* gap-4 */
}

/* Icon box */
.verify-icon-box{
    width: 40px;   /* w-10 */
    height: 40px;
    background: rgba(255,255,255,0.2); /* bg-white/20 */
    border-radius: 8px; /* rounded-lg */
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Icon */
.verify-icon{
    width: 20px; /* w-5 */
    height: 20px;
    color: #ffffff;
}

/* Title */
.verify-title{
    font-size: 1.125rem; /* text-lg */
    font-weight: 600;    /* font-semibold */
    margin: 0;
}

/* Subtitle */
.verify-subtitle{
    font-size: 0.875rem; /* text-sm */
    color: rgba(255,255,255,0.8); /* text-white/80 */
    margin: 0;
}

/* Token monospace */
.verify-token{
    font-family: monospace;
}

/* Status badge */
.status-badge{
    display: inline-flex;
    align-items: center;
    padding: 0.375rem 0.75rem; /* px-3 py-1.5 */
    border-radius: 9999px; /* rounded-full */
    font-size: 0.875rem;
    font-weight: 600;
    /* background and color are set inline for dynamic values */
}