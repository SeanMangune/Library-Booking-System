<x-mail::message>
# Booking Approved!

Hi {{ $booking->user_name }},

Great news! Your booking for **{{ $booking->room->name }}** has been approved.

**Booking Details:**
- **Date:** {{ \Carbon\Carbon::parse($booking->date)->format('F j, Y') }}
- **Time:** {{ \Carbon\Carbon::parse($booking->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('g:i A') }}
- **Purpose:** {{ $booking->title }}
- **Attendees:** {{ $booking->attendees }}

@if($booking->reason)
**Note from Librarian:**
{{ $booking->reason }}
@endif

### Access Your Room

To access your room, present the QR Code generated below to the room scanner or the librarian on duty.

<div style="text-align: center; margin: 2rem 0;">
    <img src="{{ url('/bookings/qr/'.$booking->qr_token) }}" alt="Booking QR Code" style="width: 250px; height: 250px; max-width: 100%; border-radius: 12px; border: 1px solid #e2e8f0; padding: 10px; background: white;">
    <p style="font-size: 12px; color: #64748b; margin-top: 10px;">Booking Ref: {{ $booking->booking_code }}</p>
</div>

Please arrive on time. Bookings may be forfeited if you arrive more than 15 minutes late.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
