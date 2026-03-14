<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';
        $time = $this->booking->formatted_time ?: 'N/A';

        return (new MailMessage())
            ->subject('Booking Approved')
            ->greeting('Hello,')
            ->line('Your booking request has been approved.')
            ->line('Room: ' . $roomName)
            ->line('Schedule: ' . $date . ' at ' . $time)
            ->line('Purpose: ' . ($this->booking->title ?: 'N/A'))
            ->action('View Calendar', route('calendar.index'));
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
            'url' => route('calendar.index'),
            'booking_id' => $this->booking->id,
            'status' => 'approved',
        ];
    }
}
