<?php

namespace App\Listeners;

use App\Events\PackageStatusChanged;
use App\Mail\PackageStatusChangedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPackageStatusChangedEmail implements ShouldQueue
{
    public function handle(PackageStatusChanged $event): void
    {
        $user = $event->package->user;

        Mail::to($user->email)->send(new PackageStatusChangedMail(
            $event->package,
            $event->fromStatus,
            $event->toStatus,
        ));
    }
}
