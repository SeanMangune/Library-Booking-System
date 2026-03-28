<x-mail::message>
# Booking Request Pending

Hi {{ $booking->user_name }},

Your booking request for **{{ $booking->room->name }}** has been received and is currently under review.

**Booking Details:**
- **Date:** {{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
- **Purpose:** {{ $booking->title }}
- **Attendees:** {{ $booking->attendees }}

We will notify you again once your request has been reviewed by a librarian.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
