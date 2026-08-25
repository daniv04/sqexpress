<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Enums\PackageStatus;
use App\Events\PackagePrealerted;
use App\Filament\Resources\PackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = PackageStatus::PREALERTED->value;
        $data['prealerted_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        PackagePrealerted::dispatch($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
