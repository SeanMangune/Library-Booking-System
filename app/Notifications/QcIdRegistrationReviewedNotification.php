<?php

namespace App\Notifications;

use App\Models\QcIdRegistration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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

        return $mail->action('Open SmartSpace', route('dashboard'));
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
            'url' => route('dashboard'),
            'status' => $status,
            'reviewed_at' => optional($this->registration->reviewed_at)->toDateTimeString(),
        ];
    }
}
