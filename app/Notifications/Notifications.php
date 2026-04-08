<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\QcIdRegistration;
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

class BookingRescheduledNotification extends Notification
{
    use Queueable;

    /**
     * @param array<string, mixed> $previousSchedule
     */
    public function __construct(
        private readonly Booking $booking,
        private readonly array $previousSchedule = [],
    ) {
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
        $newDate = optional($this->booking->date)->format('M d, Y') ?? 'N/A';
        $newTime = $this->booking->formatted_time ?: 'N/A';

        $previousDate = isset($this->previousSchedule['date'])
            ? optional(\Carbon\Carbon::parse((string) $this->previousSchedule['date']))->format('M d, Y')
            : null;
        $previousStart = trim((string) ($this->previousSchedule['start_time'] ?? ''));
        $previousEnd = trim((string) ($this->previousSchedule['end_time'] ?? ''));
        $previousRoom = trim((string) ($this->previousSchedule['room_name'] ?? ''));

        $previousSummaryParts = [];
        if ($previousRoom !== '' && $previousRoom !== $roomName) {
            $previousSummaryParts[] = $previousRoom;
        }
        if (! empty($previousDate)) {
            $previousSummaryParts[] = $previousDate;
        }
        if ($previousStart !== '' && $previousEnd !== '') {
            try {
                $previousSummaryParts[] = \Carbon\Carbon::parse($previousStart)->format('g:i A')
                    . ' - '
                    . \Carbon\Carbon::parse($previousEnd)->format('g:i A');
            } catch (\Throwable $exception) {
                $previousSummaryParts[] = $previousStart . ' - ' . $previousEnd;
            }
        }

        $message = 'Your booking was rescheduled to ' . $roomName . ' on ' . $newDate . ' (' . $newTime . ').';
        if (! empty($previousSummaryParts)) {
            $message .= ' Previous schedule: ' . implode(', ', $previousSummaryParts) . '.';
        }

        return [
            'title' => 'Booking rescheduled',
            'message' => $message,
            'url' => route('reservations.index'),
            'booking_id' => $this->booking->id,
            'status' => (string) $this->booking->status,
        ];
    }
}

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

class NewQcIdSubmissionForStaffNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly QcIdRegistration $registration,
        private readonly User $submittedBy,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New QC ID Registration Pending Review')
            ->greeting('Hello ' . ($notifiable->name ?? 'Staff') . ',')
            ->line('A new QC ID registration has been submitted and is awaiting review.')
            ->line('User: ' . $this->submittedBy->name)
            ->line('Submitted name: ' . ($this->registration->full_name ?: 'N/A'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New QC ID registration pending',
            'message' => $this->submittedBy->name . ' submitted a QC ID registration that needs review.',
            'url' => route('approvals.index'),
            'status' => 'pending',
            'submitted_at' => optional($this->registration->submitted_at)->toDateTimeString(),
        ];
    }
}

class QcIdRegistrationReviewedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly QcIdRegistration $registration)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = (string) $this->registration->verification_status;
        $isVerified = $status === 'verified';

        $mail = (new MailMessage())
            ->subject($isVerified ? 'QC ID Registration Approved' : 'QC ID Registration Update')
            ->greeting('Hello,')
            ->line($isVerified
                ? 'Your QC ID registration has been approved.'
                : 'Your QC ID registration was reviewed and needs attention.');

        if (! empty($this->registration->verification_notes)) {
            $mail->line('Reviewer note: ' . $this->registration->verification_notes);
        }

        return $mail->action('View QC ID Registration', route('qcid.registration.show'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = (string) $this->registration->verification_status;

        return [
            'title' => $status === 'verified' ? 'QC ID approved' : 'QC ID update',
            'message' => $status === 'verified'
                ? 'Your QC ID registration has been approved.'
                : 'Your QC ID registration was reviewed. Please check the latest status.',
            'url' => route('qcid.registration.show'),
            'status' => $status,
            'reviewed_at' => optional($this->registration->reviewed_at)->toDateTimeString(),
        ];
    }
}

class QcIdSubmissionPendingNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly QcIdRegistration $registration)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($notifiable instanceof User) {
            $channels[] = 'database';
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('QC ID Registration Submitted')
            ->greeting('Hello,')
            ->line('Your QC ID registration was submitted successfully and is now pending staff verification.')
            ->line('Submitted name: ' . ($this->registration->full_name ?: 'N/A'))
            ->action('View QC ID Registration', route('qcid.registration.show'))
            ->line('You will receive another notification once your registration has been reviewed.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'QC ID registration submitted',
            'message' => 'Your QC ID registration is now pending verification.',
            'url' => route('qcid.registration.show'),
            'status' => 'pending',
            'submitted_at' => optional($this->registration->submitted_at)->toDateTimeString(),
        ];
    }
}
