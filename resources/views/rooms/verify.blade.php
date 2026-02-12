@extends('layouts.app')

@section('title', 'Verify Booking')

@section('content')
<div class="max-w-3xl mx-auto py-10">
    <div class="bg-white rounded-2xl shadow-sm p-6 text-center">
        @if($booking)
            <div class="mb-4">
                <h2 class="text-xl font-bold">Booking verified</h2>
                <p class="text-sm text-gray-500 mt-1">Token: <code>{{ $token }}</code></p>
            </div>
            <div class="text-left bg-gray-50 rounded-lg p-4">
                <p class="text-xs text-gray-500">Room</p>
                <p class="font-semibold text-gray-900">{{ $booking->room->name }}</p>
                <p class="text-xs text-gray-500 mt-3">Date & time</p>
                <p class="font-semibold text-gray-900">{{ $booking->date->format('M j, Y') }} — {{ $booking->formatted_time }}</p>
                <p class="text-xs text-gray-500 mt-3">Booked by</p>
                <p class="font-semibold text-gray-900">{{ $booking->user_name }} &lt;{{ $booking->user_email }}&gt;</p>
            </div>
        @else
            <div class="py-8">
                <h2 class="text-lg font-semibold">Invalid or expired token</h2>
                <p class="text-sm text-gray-500 mt-2">No booking found for token <code>{{ $token }}</code>.</p>
            </div>
        @endif
    </div>
</div>
@endsection