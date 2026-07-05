<?php

namespace App\Listeners;

use App\Events\PackageUpdatedByAdmin;
use App\Mail\PackageUpdatedByAdminMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPackageUpdatedByAdminEmail implements ShouldQueue
{
    public function handle(PackageUpdatedByAdmin $event): void
    {
        $user = $event->package->user;

        Mail::to($user->email)->send(new PackageUpdatedByAdminMail(
            $event->package,
            $event->changes,
        ));
    }
}
