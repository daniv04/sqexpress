<?php

namespace App\Listeners;

use App\Events\PackageDeletedByAdmin;
use App\Mail\PackageDeletedByAdminMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendPackageDeletedByAdminEmail implements ShouldQueue
{
    public function handle(PackageDeletedByAdmin $event): void
    {
        Mail::to($event->userEmail)->send(new PackageDeletedByAdminMail(
            $event->tracking,
            $event->userName,
            $event->description,
        ));
    }
}
