<?php

namespace App\Listeners;

use App\Events\PackagePrealerted;
use App\Mail\PackagePrealertedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPackagePrealertedEmail implements ShouldQueue
{
    public function handle(PackagePrealerted $event): void
    {
        $user = $event->package->user;

        Mail::to($user->email)->send(new PackagePrealertedMail($event->package));
    }
}
