<?php

namespace App\Events;

use App\Models\Package;
use Illuminate\Foundation\Events\Dispatchable;

class PackagePrealerted
{
    use Dispatchable;

    public function __construct(public readonly Package $package) {}
}
