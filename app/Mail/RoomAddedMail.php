<?php

namespace App\Mail;

use App\Models\Room;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoomAddedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Room $room)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Exciting News! A New Room is Available at SmartSpace',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rooms.added',
        );
    }
}
