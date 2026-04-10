@php
    $bookingDate = \Carbon\Carbon::parse($booking->date)->format('F j, Y');
    $bookingTime = \Carbon\Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($booking->end_time)->format('g:i A');
    $verifyUrl = $booking->qr_token ? url('/verify?token=' . $booking->qr_token) : null;
    $qrPreviewUrl = $booking->qr_token ? url('/bookings/qr/' . $booking->qr_token . '?format=png') : null;
    $qrPngDownloadUrl = $booking->qr_token ? url('/bookings/qr/' . $booking->qr_token . '?format=png&download=1') : null;
    $qrJpegDownloadUrl = $booking->qr_token ? url('/bookings/qr/' . $booking->qr_token . '?format=jpeg&download=1') : null;
@endphp

<x-mail::message>
<div style="background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border-radius: 16px; padding: 24px; color: #ffffff; margin-bottom: 18px;">
    <p style="margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.9;">SmartSpace Booking Status</p>
    <h1 style="margin: 0; font-size: 24px; line-height: 1.25; color: #ffffff;">Booking Approved</h1>
    <p style="margin: 10px 0 0; font-size: 14px; color: #e0e7ff;">Your reservation is confirmed and ready for check-in.</p>
</div>

<p style="margin: 0 0 14px; font-size: 15px; color: #1f2937;">Hi <strong>{{ $booking->user_name }}</strong>,</p>

<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 16px 18px; margin-bottom: 16px;">
    <p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280;">Booking Details</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size: 14px; color: #111827;">
        <tr>
            <td style="padding: 6px 0; color: #6b7280; width: 34%;">Room</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->room->name }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Date</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $bookingDate }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Time</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $bookingTime }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Purpose</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->title }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Attendees</td>
            <td style="padding: 6px 0; font-weight: 700;">{{ $booking->attendees }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Reference</td>
            <td style="padding: 6px 0; font-weight: 700; color: #4f46e5;">{{ $booking->booking_code }}</td>
        </tr>
    </table>
</div>

@if($booking->reason)
<div style="border: 1px solid #ddd6fe; border-radius: 12px; background: #f5f3ff; padding: 12px 14px; margin-bottom: 16px;">
    <p style="margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #6d28d9;">Librarian Note</p>
    <p style="margin: 0; font-size: 14px; color: #4c1d95;">{{ $booking->reason }}</p>
</div>
@endif

@if($booking->qr_token)
<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #f8fafc; padding: 18px; text-align: center; margin-bottom: 16px;">
    <p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #6b7280;">Access QR Code</p>
    <img src="{{ $qrPreviewUrl }}" alt="Booking QR Code" style="display: block; margin: 0 auto; width: 210px; max-width: 100%; border-radius: 10px; border: 1px solid #dbeafe; padding: 8px; background: #ffffff;">
    <p style="margin: 10px 0 0; font-size: 12px; color: #64748b;">Present this QR code at the scanner or librarian desk.</p>
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 16px;">
    <tr>
        <td style="padding-right: 6px;">
            <a href="{{ $qrPngDownloadUrl }}" style="display: block; text-align: center; background: #111827; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 10px 12px; border-radius: 10px;">Download PNG</a>
        </td>
        <td style="padding-left: 6px;">
            <a href="{{ $qrJpegDownloadUrl }}" style="display: block; text-align: center; background: #374151; color: #ffffff; text-decoration: none; font-size: 13px; font-weight: 700; padding: 10px 12px; border-radius: 10px;">Download JPEG</a>
        </td>
    </tr>
</table>

<x-mail::button :url="$verifyUrl">
Open Verification Page
</x-mail::button>
@endif

<p style="margin-top: 16px; color: #4b5563; font-size: 13px;">Please arrive on time. Late arrivals may affect room access.</p>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
