<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingTimeAlertNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingTimeAlertService
{
    public function syncForUser(User $user, bool $isStaff, ?Carbon $reference = null): void
    {
        if (! Schema::hasTable('bookings') || ! Schema::hasTable('notifications')) {
            return;
        }

        $reference = $reference ?? now(config('app.booking_timezone', 'Asia/Manila'));

        if ($isStaff) {
            $this->ensureBookingTimeAlertNotificationsForStaff($reference);
        }

        $this->ensureBookingTimeAlertNotificationsForUser($user, $reference);
    }

    private function ensureBookingTimeAlertNotificationsForUser(User $user, Carbon $reference): void
    {
        $bookings = Booking::with('room')
            ->where('status', 'approved')
            ->where('user_id', $user->id)
            ->whereDate('date', $reference->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();

        foreach ($bookings as $booking) {
            $threshold = $this->getCurrentTimeThreshold($booking, $reference);
            if ($threshold === null) {
                continue;
            }

            $payload = $this->buildBookingTimeAlertPayload($booking, $reference, $threshold, false);
            if ($payload === null || $this->bookingAlertExists($user, $payload['alert_key'])) {
                continue;
            }

            Notification::sendNow($user, new BookingTimeAlertNotification(
                $booking,
                $payload['title'],
                $payload['message'],
                $payload['url'],
                $payload['alert_key'],
            ));
        }
    }

    private function ensureBookingTimeAlertNotificationsForStaff(Carbon $reference): void
    {
        $bookings = Booking::with('room', 'user')
            ->where('status', 'approved')
            ->whereDate('date', $reference->toDateString())
            ->whereNotNull('start_time')
            ->whereNotNull('end_time')
            ->get();

        if ($bookings->isEmpty()) {
            return;
        }

        $staffMembers = User::whereIn('role', ['admin', 'librarian'])->get();
        if ($staffMembers->isEmpty()) {
            return;
        }

        foreach ($bookings as $booking) {
            $threshold = $this->getCurrentTimeThreshold($booking, $reference);
            if ($threshold === null) {
                continue;
            }

            $payload = $this->buildBookingTimeAlertPayload($booking, $reference, $threshold, true);
            if ($payload === null) {
                continue;
            }

            foreach ($staffMembers as $staff) {
                if ($this->bookingAlertExists($staff, $payload['alert_key'])) {
                    continue;
                }

                Notification::sendNow($staff, new BookingTimeAlertNotification(
                    $booking,
                    $payload['title'],
                    $payload['message'],
                    $payload['url'],
                    $payload['alert_key'],
                ));
            }
        }
    }

    private function buildBookingTimeAlertPayload(Booking $booking, Carbon $reference, int $threshold, bool $forStaff): ?array
    {
        if (! $booking->room || $booking->room->effectiveStatus() === 'maintenance') {
            return null;
        }

        $date = $booking->date?->format('Y-m-d');
        if (! $date || blank($booking->start_time) || blank($booking->end_time)) {
            return null;
        }

        try {
            $timezone = config('app.booking_timezone', 'Asia/Manila');
            $start = Carbon::parse($date . ' ' . $booking->start_time, $timezone);
            $end = Carbon::parse($date . ' ' . $booking->end_time, $timezone);
        } catch (\Throwable $exception) {
            return null;
        }

        $secondsLeft = $reference->diffInSeconds($end, false);

        if ($threshold === 0) {
            if ($secondsLeft > 0) {
                return null;
            }

            if ($booking->qr_validity !== 'valid' && $booking->determineQrValidity($reference) !== 'valid') {
                return null;
            }
        } else {
            if ($secondsLeft <= 0) {
                return null;
            }

            if ($booking->qr_validity !== 'valid' && $booking->determineQrValidity($reference) !== 'valid') {
                return null;
            }

            if ($threshold === 20) {
                $minutesLeft = (int) ceil($secondsLeft / 60);
                if ($minutesLeft < 11 || $minutesLeft > 20) {
                    return null;
                }
            }

            if ($threshold === 10) {
                $minutesLeft = (int) ceil($secondsLeft / 60);
                if ($minutesLeft < 1 || $minutesLeft > 10) {
                    return null;
                }
            }
        }

        $roomName = $booking->room->name ?: 'Room';
        $userName = $booking->user_name ?: 'User';
        $endLabel = $end->format('g:i A');
        $key = $this->bookingTimeAlertKey($booking, $threshold);

        if ($threshold === 20) {
            $title = $forStaff
                ? "Booking for {$roomName} ends in 20 minutes"
                : "20 minutes left for your booking";
            $message = $forStaff
                ? "{$userName}'s booking for {$roomName} ends at {$endLabel}. Please prepare the room for the next reservation."
                : "Your booking for {$roomName} ends in 20 minutes at {$endLabel}. Please prepare to release the room on time.";
        } elseif ($threshold === 10) {
            $title = $forStaff
                ? "Booking for {$roomName} ends in 10 minutes"
                : "10 minutes left for your booking";
            $message = $forStaff
                ? "{$userName}'s booking for {$roomName} ends at {$endLabel}. Ensure the room is ready for the next group."
                : "Your booking for {$roomName} ends in 10 minutes at {$endLabel}. Please finish up and hand over the room promptly.";
        } else {
            $title = $forStaff
                ? "Booking has expired"
                : "Your booking has expired";
            $message = $forStaff
                ? "{$userName}'s booking for {$roomName} expired at {$endLabel}. Confirm the room is available again."
                : "Your booking for {$roomName} has expired at {$endLabel}. Please vacate the room immediately if you have not already.";
        }

        return [
            'title' => $title,
            'message' => $message,
            'url' => $forStaff ? route('dashboard') : route('reservations.index'),
            'alert_key' => $key,
        ];
    }

    private function bookingAlertExists(User $user, string $alertKey): bool
    {
        $query = $user->notifications();

        if (DB::getDriverName() === 'pgsql') {
            return $query
                ->whereRaw('(data::json ->> ?) = ?', ['alert_key', $alertKey])
                ->exists();
        }

        return $query
            ->where('data->alert_key', $alertKey)
            ->exists();
    }

    private function bookingTimeAlertKey(Booking $booking, int $threshold): string
    {
        return sprintf('booking.%d.time_left.%d', $booking->id, $threshold);
    }

    private function getCurrentTimeThreshold(Booking $booking, Carbon $reference): ?int
    {
        if (blank($booking->start_time) || blank($booking->end_time) || blank($booking->date)) {
            return null;
        }

        try {
            $timezone = config('app.booking_timezone', 'Asia/Manila');
            $end = Carbon::parse($booking->date->format('Y-m-d') . ' ' . $booking->end_time, $timezone);
        } catch (\Throwable $exception) {
            return null;
        }

        $secondsLeft = $reference->diffInSeconds($end, false);

        if ($secondsLeft <= 0) {
            return 0;
        }

        if ($secondsLeft <= 10 * 60) {
            return 10;
        }

        if ($secondsLeft <= 20 * 60) {
            return 20;
        }

        return null;
    }
}
