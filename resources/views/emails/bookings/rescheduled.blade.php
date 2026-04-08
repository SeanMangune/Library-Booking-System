@php
    $previousRoomName = trim((string) ($previousSchedule['room_name'] ?? ''));
    $previousDateRaw = (string) ($previousSchedule['date'] ?? '');
    $previousStartRaw = (string) ($previousSchedule['start_time'] ?? '');
    $previousEndRaw = (string) ($previousSchedule['end_time'] ?? '');

    $previousDate = $previousDateRaw !== ''
        ? \Carbon\Carbon::parse($previousDateRaw)->format('F j, Y')
        : 'N/A';

    $previousTime = 'N/A';
    if ($previousStartRaw !== '' && $previousEndRaw !== '') {
        $previousTime = \Carbon\Carbon::parse($previousStartRaw)->format('g:i A')
            . ' - '
            . \Carbon\Carbon::parse($previousEndRaw)->format('g:i A');
    }
@endphp

<x-mail::message>
# Booking Rescheduled

Hi {{ $booking->user_name }},

Your booking schedule has been updated.

**Previous Schedule:**
- **Room:** {{ $previousRoomName !== '' ? $previousRoomName : ($booking->room->name ?? 'Room') }}
- **Date:** {{ $previousDate }}
- **Time:** {{ $previousTime }}

**Updated Schedule:**
- **Room:** {{ $booking->room->name ?? 'Room' }}
- **Date:** {{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
- **Purpose:** {{ $booking->title }}
- **Attendees:** {{ $booking->attendees }}

<x-mail::button :url="route('reservations.index')">
View My Reservations
</x-mail::button>

Please review your updated schedule and arrive on time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
