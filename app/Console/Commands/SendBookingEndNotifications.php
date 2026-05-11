<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BookingCompletedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBookingEndNotifications extends Command
{
    protected $signature = 'bookings:send-end-notifications
                            {--grace=5 : Grace period in minutes after end_time before sending}';

    protected $description = 'Send notifications for bookings that have just ended (scheduled task).';

    public function handle(): int
    {
        $timezone = (string) config('app.booking_timezone', 'Asia/Manila');
        $now = now($timezone);
        $gracePeriod = (int) $this->option('grace');

        // Find approved bookings that ended recently (within the last run window + grace)
        // and haven't been notified yet.
        $cutoffStart = $now->copy()->subMinutes($gracePeriod + 10);
        $cutoffEnd = $now->copy()->subMinutes($gracePeriod);

        $bookings = Booking::with('room', 'user')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', 'approved')
            ->whereDate('date', $now->toDateString())
            ->whereNotNull('end_time')
            ->whereTime('end_time', '>=', $cutoffStart->format('H:i:s'))
            ->whereTime('end_time', '<=', $cutoffEnd->format('H:i:s'))
            ->whereNull('end_notified_at')
            ->get();

        if ($bookings->isEmpty()) {
            $this->info('No bookings to notify.');

            return self::SUCCESS;
        }

        $notifiedCount = 0;

        foreach ($bookings as $booking) {
            $user = $booking->user;

            if (! $user) {
                $this->warn("Booking #{$booking->id}: no associated user, skipping.");

                continue;
            }

            try {
                $user->notify(new BookingCompletedNotification($booking));

                $booking->forceFill(['end_notified_at' => $now])->saveQuietly();

                $notifiedCount++;

                $this->info("Booking #{$booking->id} ({$booking->room?->name}): notification sent to {$user->email}.");
            } catch (\Throwable $exception) {
                $this->error("Booking #{$booking->id}: failed — {$exception->getMessage()}");
            }
        }

        $this->info("Done. Sent {$notifiedCount} notification(s).");

        return self::SUCCESS;
    }
}
