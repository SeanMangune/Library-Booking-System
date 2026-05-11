<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Booking $booking,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database', 'mail'];

        if ($this->shouldBroadcast()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';
        $time = $this->booking->formatted_time ?: 'N/A';

        return (new MailMessage())
            ->subject('Booking Completed — ' . $roomName)
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line('Your booking session has ended.')
            ->line('Room: ' . $roomName)
            ->line('Date: ' . $date)
            ->line('Time: ' . $time)
            ->line('Thank you for using SmartSpace! We hope your session was productive.')
            ->action('View My Reservations', route('reservations.index'));
    }

    private function shouldBroadcast(): bool
    {
        $defaultConnection = (string) config('broadcasting.default', 'null');
        if ($defaultConnection === '' || $defaultConnection === 'null') {
            return false;
        }

        if ($defaultConnection === 'reverb') {
            return filled(config('broadcasting.connections.reverb.app_id'))
                && filled(config('broadcasting.connections.reverb.key'))
                && filled(config('broadcasting.connections.reverb.secret'));
        }

        if ($defaultConnection === 'pusher') {
            return filled(config('broadcasting.connections.pusher.app_id'))
                && filled(config('broadcasting.connections.pusher.key'))
                && filled(config('broadcasting.connections.pusher.secret'));
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';
        $time = $this->booking->formatted_time ?: 'N/A';

        return [
            'title' => 'Booking completed',
            'message' => 'Your booking for ' . $roomName . ' on ' . $date . ' (' . $time . ') has ended.',
            'url' => route('reservations.index'),
            'booking_id' => $this->booking->id,
            'room_id' => $this->booking->room_id,
            'room_name' => $roomName,
            'alert_type' => 'booking_completed',
            'status' => (string) $this->booking->status,
        ];
    }
}
