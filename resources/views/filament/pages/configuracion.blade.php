<x-filament-panels::page>
    <x-filament-panels::form wire:submit="save">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
        />
    </x-filament-panels::form>

    <x-filament::section>
        <x-slot name="heading">Guía de estados del paquete</x-slot>
        <x-slot name="description">Referencia rápida de cada estado y su significado, en el orden en que normalmente avanza un paquete.</x-slot>

        <div class="divide-y divide-gray-200 dark:divide-white/10">
            @foreach ($this->getPackageStatuses() as $status)
                <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-start sm:gap-4">
                    <div class="shrink-0 sm:w-48">
                        <x-filament::badge :color="$status['color']">
                            {{ $status['label'] }}
                        </x-filament::badge>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 sm:mt-0.5">{{ $status['description'] }}</p>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
