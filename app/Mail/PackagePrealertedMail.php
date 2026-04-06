<?php

namespace App\Mail;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackagePrealertedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Package $package) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Prealerta recibida — ' . $this->package->tracking,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.package-prealerted',
        );
    }
}
