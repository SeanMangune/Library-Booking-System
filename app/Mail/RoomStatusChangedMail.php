<?php

namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoomStatusChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Room $room, public string $status)
    {
    }

    public function envelope(): Envelope
    {
        $action = $this->status === 'active' ? 'is Back Open' : 'is Temporarily Closed';
        return new Envelope(
            subject: "Update: {$this->room->name} {$action}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rooms.status_changed',
        );
    }
}
