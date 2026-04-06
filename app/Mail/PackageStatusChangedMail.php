<?php

namespace App\Mail;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Package $package,
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu paquete actualizó su estado — ' . $this->package->tracking,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.package-status-changed',
        );
    }
}
