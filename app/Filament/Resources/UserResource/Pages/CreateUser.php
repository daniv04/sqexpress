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

        // Si no hay locker_code o está vacío, generar automáticamente
        $hasLockerCode = isset($data['locker_code']) && $data['locker_code'] !== null && $data['locker_code'] !== '';

        if (!$hasLockerCode) {
            $data['locker_code'] = $lockerCodeService->generateNextLockerCode();
        }

        return static::getModel()::create($data);
    }
}
