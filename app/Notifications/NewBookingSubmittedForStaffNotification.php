<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingSubmittedForStaffNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Booking $booking)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';
        $time = $this->booking->formatted_time ?: 'N/A';

        return (new MailMessage())
            ->subject('New Booking Submitted')
            ->greeting('Hello ' . ($notifiable->name ?? 'Admin') . ',')
            ->line('A user submitted a new booking request that needs staff visibility.')
            ->line('User: ' . ($this->booking->user_name ?: 'N/A'))
            ->line('Room: ' . $roomName)
            ->line('Schedule: ' . $date . ' at ' . $time)
            ->line('Status: ' . ucfirst((string) $this->booking->status))
            ->action('Open Booking Approvals', route('approvals.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $roomName = $this->booking->room?->name ?? 'Room';
        $date = optional($this->booking->date)->format('M d, Y') ?? 'N/A';

        return [
            'title' => 'New booking submitted',
            'message' => ($this->booking->user_name ?: 'A user') . ' submitted a booking for ' . $roomName . ' on ' . $date . '.',
            'url' => route('approvals.index'),
            'status' => (string) $this->booking->status,
            'booking_id' => $this->booking->id,
            'submitted_at' => optional($this->booking->created_at)->toDateTimeString(),
        ];
    }
}
