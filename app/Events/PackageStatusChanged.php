<?php

namespace App\Events;

use App\Models\Package;
use Illuminate\Foundation\Events\Dispatchable;

class PackageStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Package $package,
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {}
}
