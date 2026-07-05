<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageDeletedByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $tracking,
        public readonly string $userName,
        public readonly ?string $description,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu paquete fue eliminado — ' . $this->tracking,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.package-deleted-by-admin',
        );
    }
}
