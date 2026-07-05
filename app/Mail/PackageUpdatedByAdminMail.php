<?php

namespace App\Mail;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PackageUpdatedByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Package $package,
        public readonly array $changes,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Actualizamos la información de tu paquete — ' . $this->package->tracking,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.package-updated-by-admin',
        );
    }
}
