<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'no_entregados' => Tab::make('No entregados')
                ->modifyQueryUsing(fn ($query) => $query->notFullyDelivered()),
            'entregados' => Tab::make('Entregados')
                ->modifyQueryUsing(fn ($query) => $query->fullyDelivered()),
        ];
    }
}
