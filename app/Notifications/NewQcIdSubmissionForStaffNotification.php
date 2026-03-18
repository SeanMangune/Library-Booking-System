<?php

namespace App\Notifications;

use App\Models\QcIdRegistration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

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
