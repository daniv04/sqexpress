<?php

namespace App\Filament\Resources\PackageResource\Pages;

use App\Enums\PackageStatus;
use App\Events\PackagePrealerted;
use App\Filament\Resources\PackageResource;
use App\Models\PackageStatusHistory;
use App\Services\DbService\PackageService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

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
        PackageStatusHistory::create([
            'package_id' => $this->record->id,
            'from_status' => null,
            'to_status' => PackageStatus::PREALERTED->value,
            'changed_by' => Auth::id(),
            'note' => 'Prealerta creada por administrador.',
        ]);

        if ($this->data['arrived_at_office'] ?? false) {
            // Already physically at the office: no need to register the
            // prealert with the forwarder or send the prealert email.
            app(PackageService::class)->receiveUnannouncedPackage(
                package: $this->record,
                shelfLocation: (string) $this->record->shelf_location,
                weight: $this->record->weight !== null ? (float) $this->record->weight : null,
                changedBy: Auth::id(),
            );

            return;
        }

        PackagePrealerted::dispatch($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
