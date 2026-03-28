<?php

namespace App\Notifications;

use App\Models\QcIdRegistration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
        $verified = $this->registration->verification_status === 'verified';

        if ($verified) {
            return (new MailMessage())
                ->subject('Your QC ID is verified')
                ->greeting('Congratulations!')
                ->line('Your Quezon City Citizen ID was verified successfully. You can book library rooms anytime from SmartSpace.')
                ->line('Registered name: ' . ($this->registration->full_name ?: 'N/A'))
                ->action('Go to dashboard', route('dashboard'));
        }

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
        $verified = $this->registration->verification_status === 'verified';

        if ($verified) {
            return [
                'title' => 'QC ID verified',
                'message' => 'Congratulations! Your Quezon City Citizen ID was verified successfully. You can book rooms anytime.',
                'url' => route('dashboard'),
                'status' => 'verified',
                'submitted_at' => optional($this->registration->submitted_at)->toDateTimeString(),
            ];
        }

        return [
            'title' => 'QC ID registration submitted',
            'message' => 'Your QC ID registration is now pending staff verification.',
            'url' => route('qcid.registration.show'),
            'status' => 'pending',
            'submitted_at' => optional($this->registration->submitted_at)->toDateTimeString(),
        ];
    }
}
