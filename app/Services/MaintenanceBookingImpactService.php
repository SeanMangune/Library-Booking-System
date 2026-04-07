<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class MaintenanceBookingImpactService
{
    private const ACTIVE_BOOKING_STATUSES = ['pending', 'approved'];

    public function getAffectedBookings(
        Room $room,
        CarbonInterface $maintenanceStartAt,
        CarbonInterface $maintenanceEndAt
    ): Collection {
        if ($maintenanceEndAt->lessThanOrEqualTo($maintenanceStartAt)) {
            return collect();
        }

        $candidateBookings = Booking::query()
            ->with(['room', 'user'])
            ->where('room_id', $room->id)
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->whereDate('date', '>=', $maintenanceStartAt->toDateString())
            ->whereDate('date', '<=', $maintenanceEndAt->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        $now = now();

        return $candidateBookings
            ->filter(function (Booking $booking) use ($maintenanceStartAt, $maintenanceEndAt, $now): bool {
                $bookingRange = $this->bookingDateTimeRange($booking);
                if (! $bookingRange) {
                    return false;
                }

                [$bookingStartAt, $bookingEndAt] = $bookingRange;

                if ($bookingEndAt->lessThanOrEqualTo($now)) {
                    return false;
                }

                return $this->windowsOverlap(
                    $bookingStartAt,
                    $bookingEndAt,
                    Carbon::instance($maintenanceStartAt),
                    Carbon::instance($maintenanceEndAt)
                );
            })
            ->values();
    }

    private function bookingDateTimeRange(Booking $booking): ?array
    {
        if (blank($booking->date) || blank($booking->start_time) || blank($booking->end_time)) {
            return null;
        }

        $dateString = $booking->date instanceof CarbonInterface
            ? $booking->date->format('Y-m-d')
            : (string) $booking->date;

        try {
            $bookingStartAt = Carbon::parse($dateString . ' ' . (string) $booking->start_time);
            $bookingEndAt = Carbon::parse($dateString . ' ' . (string) $booking->end_time);
        } catch (\Throwable) {
            return null;
        }

        if ($bookingEndAt->lessThanOrEqualTo($bookingStartAt)) {
            $bookingEndAt->addDay();
        }

        return [$bookingStartAt, $bookingEndAt];
    }

    private function windowsOverlap(
        CarbonInterface $windowAStart,
        CarbonInterface $windowAEnd,
        CarbonInterface $windowBStart,
        CarbonInterface $windowBEnd
    ): bool {
        return $windowAStart->lt($windowBEnd) && $windowAEnd->gt($windowBStart);
    }

}
