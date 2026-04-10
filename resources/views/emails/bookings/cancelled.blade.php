@php
	$bookingDate = \Carbon\Carbon::parse($booking->date)->format('F j, Y');
	$bookingTime = \Carbon\Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($booking->end_time)->format('g:i A');
	$cancelMessage = $cancelledBy === 'admin'
		? 'This booking was cancelled by a librarian.'
		: 'This booking was cancelled based on your request.';
@endphp

<x-mail::message>
<div style="background: linear-gradient(135deg, #475569 0%, #1f2937 100%); border-radius: 16px; padding: 24px; color: #ffffff; margin-bottom: 18px;">
	<p style="margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.9;">SmartSpace Booking Status</p>
	<h1 style="margin: 0; font-size: 24px; line-height: 1.25; color: #ffffff;">Booking Cancelled</h1>
	<p style="margin: 10px 0 0; font-size: 14px; color: #e2e8f0;">Your schedule is no longer active in the reservation queue.</p>
</div>

<p style="margin: 0 0 14px; font-size: 15px; color: #1f2937;">Hi <strong>{{ $booking->user_name }}</strong>,</p>

<div style="border: 1px solid #cbd5e1; border-radius: 14px; background: #f8fafc; padding: 14px 16px; margin-bottom: 14px;">
	<p style="margin: 0; font-size: 14px; color: #334155;">{{ $cancelMessage }}</p>
</div>

<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 16px 18px; margin-bottom: 14px;">
	<p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280;">Cancelled Booking Details</p>
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
	</table>
</div>

@if($cancelledBy === 'admin')
<p style="margin-top: 0; color: #4b5563; font-size: 13px;">If you believe this was cancelled in error, please contact the library admin desk.</p>
@endif

<x-mail::button :url="config('app.url') . '/dashboard'">
View Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
