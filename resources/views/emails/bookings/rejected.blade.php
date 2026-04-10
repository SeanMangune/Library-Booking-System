@php
	$bookingDate = \Carbon\Carbon::parse($booking->date)->format('F j, Y');
	$bookingTime = \Carbon\Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($booking->end_time)->format('g:i A');
	$reason = trim((string) ($booking->reason ?? ''));
@endphp

<x-mail::message>
<div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border-radius: 16px; padding: 24px; color: #ffffff; margin-bottom: 18px;">
	<p style="margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.9;">SmartSpace Booking Status</p>
	<h1 style="margin: 0; font-size: 24px; line-height: 1.25; color: #ffffff;">Booking Request Not Approved</h1>
	<p style="margin: 10px 0 0; font-size: 14px; color: #fee2e2;">This schedule could not be accommodated at the moment.</p>
</div>

<p style="margin: 0 0 14px; font-size: 15px; color: #1f2937;">Hi <strong>{{ $booking->user_name }}</strong>,</p>

<div style="border: 1px solid #fecaca; border-radius: 14px; background: #fef2f2; padding: 14px 16px; margin-bottom: 14px;">
	<p style="margin: 0; font-size: 14px; color: #991b1b;">We were unable to approve your request for <strong>{{ $booking->room->name }}</strong>.</p>
</div>

<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 16px 18px; margin-bottom: 14px;">
	<p style="margin: 0 0 10px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280;">Request Details</p>
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
	</table>
</div>

<div style="border: 1px solid #ddd6fe; border-radius: 12px; background: #f5f3ff; padding: 12px 14px; margin-bottom: 16px;">
	<p style="margin: 0 0 6px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #6d28d9;">Reason</p>
	<p style="margin: 0; font-size: 14px; color: #4c1d95;">{{ $reason !== '' ? $reason : 'No specific reason was provided. Usually this means the room became unavailable or the capacity policy was exceeded.' }}</p>
</div>

<x-mail::button :url="route('login')">
Find Another Room
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
