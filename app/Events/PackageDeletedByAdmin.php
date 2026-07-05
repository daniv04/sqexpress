<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class PackageDeletedByAdmin
{
    use Dispatchable;

    // Carries scalars instead of the Package model: SerializesModels on the queued listener
    // would try to reload this (now deleted) row from the DB and throw ModelNotFoundException,
    // silently dropping the email.
    public function __construct(
        public readonly string $tracking,
        public readonly string $userEmail,
        public readonly string $userName,
        public readonly ?string $description,
    ) {}
}
