@php
	$bookingDate = \Carbon\Carbon::parse($booking->date)->format('F j, Y');
	$bookingTime = \Carbon\Carbon::parse($booking->start_time)->format('g:i A') . ' - ' . \Carbon\Carbon::parse($booking->end_time)->format('g:i A');
@endphp

<x-mail::message>
<div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border-radius: 16px; padding: 24px; color: #ffffff; margin-bottom: 18px;">
	<p style="margin: 0 0 6px; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.9;">SmartSpace Booking Status</p>
	<h1 style="margin: 0; font-size: 24px; line-height: 1.25; color: #ffffff;">Booking Pending Review</h1>
	<p style="margin: 10px 0 0; font-size: 14px; color: #fde68a;">Your request is in the librarian approval queue.</p>
</div>

<p style="margin: 0 0 14px; font-size: 15px; color: #1f2937;">Hi <strong>{{ $booking->user_name }}</strong>,</p>

<div style="border: 1px solid #fde68a; border-radius: 14px; background: #fffbeb; padding: 14px 16px; margin-bottom: 14px;">
	<p style="margin: 0; font-size: 14px; color: #92400e;">Your booking request for <strong>{{ $booking->room->name }}</strong> has been received and is waiting for librarian approval.</p>
</div>

<div style="border: 1px solid #e5e7eb; border-radius: 14px; background: #ffffff; padding: 16px 18px; margin-bottom: 16px;">
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
		<tr>
			<td style="padding: 6px 0; color: #6b7280;">Attendees</td>
			<td style="padding: 6px 0; font-weight: 700;">{{ $booking->attendees }}</td>
		</tr>
	</table>
</div>

<p style="margin-top: 0; color: #4b5563; font-size: 13px;">You will receive another email as soon as the review is completed.</p>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
