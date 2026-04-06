<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;

class PackagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Package $package): bool
    {
        return $user->role === 'admin' || $package->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Package $package): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $package->user_id === $user->id && $package->status === 'prealerted';
    }

    public function delete(User $user, Package $package): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $package->user_id === $user->id && $package->status === 'prealerted';
    }
}
