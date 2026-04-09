<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingTimeAlertNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
        private readonly string $title,
        private readonly string $message,
        private readonly string $url,
        private readonly string $alertKey,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'room_name' => $this->booking->room?->name,
            'alert_key' => $this->alertKey,
            'alert_type' => 'booking_time_left',
            'status' => (string) $this->booking->status,
        ];
    }
}
