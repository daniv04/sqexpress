<?php

namespace App\Http\Middleware;

class FilamentAuthenticate extends \Filament\Http\Middleware\Authenticate
{
    protected function redirectTo($request): ?string
    {
        return route('login');
    }
}
