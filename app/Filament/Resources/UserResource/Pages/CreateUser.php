<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\DbService\LockerCodeService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $lockerCodeService = app(LockerCodeService::class);
        $data['locker_code'] = $lockerCodeService->generateNextLockerCode();

        return static::getModel()::create($data);
    }
}
