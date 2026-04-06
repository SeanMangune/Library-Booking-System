<x-mail::message>
# Booking Cancelled

Hi {{ $booking->user_name }},

@if($cancelledBy === 'admin')
Your booking for **{{ $booking->room->name }}** has been cancelled by a librarian.
@else
Your booking for **{{ $booking->room->name }}** has been successfully cancelled as requested.
@endif

**Cancelled Booking Details:**
- **Date:** {{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
- **Purpose:** {{ $booking->title }}
- **Attendees:** {{ $booking->attendees }}

@if($cancelledBy === 'admin')
If you believe this cancellation was made in error, please contact us or visit the library admin desk.
@endif

You can always book another available time slot from your dashboard.

<x-mail::button :url="config('app.url') . '/dashboard'">
View Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
