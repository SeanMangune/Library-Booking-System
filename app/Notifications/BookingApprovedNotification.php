<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }



    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';

        return [
            'title' => 'Booking approved',
            'message' => 'Your booking for ' . $roomName . ' on ' . $date . ' was approved.',
            'url' => route('reservations.index'),
            'booking_id' => $this->booking->id,
            'status' => 'approved',
        ];
    }
}
