<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
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
        $channels = ['database', 'mail'];

        if ($this->shouldBroadcast()) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject($this->title)
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
            ->line($this->message)
            ->action('Open Booking Details', $this->url);
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
