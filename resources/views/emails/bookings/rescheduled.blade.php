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
<div style="background: linear-gradient(135deg, #0ea5e9 0%, #4f46e5 100%); border-radius: 16px; padding: 24px; color: #ffffff; margin-bottom: 18px;">
    <p style="margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.9;">SmartSpace Booking Status</p>
    <h1 style="margin: 0; font-size: 24px; line-height: 1.25; color: #ffffff;">Booking Rescheduled</h1>
    <p style="margin: 10px 0 0; font-size: 14px; color: #dbeafe;">Your reservation details were updated by the library team.</p>
</div>

<p style="margin: 0 0 14px; font-size: 15px; color: #1f2937;">Hi <strong>{{ $booking->user_name }}</strong>,</p>

<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 16px 18px; margin-bottom: 14px;">
    <p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280;">Previous Schedule</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #111827;">
        <tr>
            <td style="padding: 6px 0; color: #6b7280; width: 34%;">Room</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $previousRoomName !== '' ? $previousRoomName : ($booking->room->name ?? 'Room') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Date</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $previousDate }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Time</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $previousTime }}</td>
        </tr>
    </table>
</div>

<div style="border: 1px solid #bfdbfe; border-radius: 14px; background: #eff6ff; padding: 16px 18px; margin-bottom: 16px;">
    <p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #1d4ed8;">Updated Schedule</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #111827;">
        <tr>
            <td style="padding: 6px 0; color: #6b7280; width: 34%;">Room</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->room->name ?? 'Room' }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Date</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Time</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Purpose</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->title }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Attendees</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->attendees }}</td>
        </tr>
    </table>
</div>

<x-mail::button :url="route('reservations.index')">
View My Reservations
</x-mail::button>

<p style="margin-top: 16px; color: #4b5563; font-size: 13px;">Please review your updated schedule and arrive on time.</p>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
