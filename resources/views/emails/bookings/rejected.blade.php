<x-mail::message>
# Booking Update

Hi {{ $booking->user_name }},

We're sorry, but your booking request for **{{ $booking->room->name }}** could not be accommodated at this time.

**Booking Request Details:**
- **Date:** {{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
- **Purpose:** {{ $booking->title }}

@if($booking->reason)
**Reason:**
{{ $booking->reason }}
@else
**Reason:**
No specific reason was provided. Usually, this means the room became unavailable or the limits were exceeded.
@endif

We hope to see you in the SmartSpace Library soon. Feel free to log in and look for another available time slot!

<x-mail::button :url="route('login')">
Find Another Room
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
