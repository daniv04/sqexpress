<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Datos de mi casillero') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Usa esta dirección para recibir tus paquetes según el método de envío.') }}
        </p>
    </header>

    @if($shippingMethods->isEmpty())
        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            {{ __('No hay casilleros configurados por el momento.') }}
        </p>
    @else
        <div
            x-data="{ activeTab: '{{ $shippingMethods->first()->id }}' }"
            class="mt-4"
        >
            {{-- Tabs --}}
            <div class="flex flex-wrap gap-2 mb-4">
                @foreach($shippingMethods as $method)
                    <button
                        type="button"
                        @click="activeTab = '{{ $method->id }}'"
                        :class="activeTab === '{{ $method->id }}'
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600'"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                    >
                        {{ $method->name }}
                    </button>
                @endforeach
            </div>

            {{-- Cards --}}
            @foreach($shippingMethods as $method)
                @php
                    if ($method->nombre_en_campo === 'nombre') {
                        $nombre   = $user->name;
                        $apellido = $method->complemento_nombre ?? '';
                    } else {
                        $nombre   = $method->complemento_nombre ?? '';
                        $apellido = $user->name;
                    }
                @endphp

                <div x-show="activeTab === '{{ $method->id }}'" x-cloak>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            {{ $method->name }}
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Nombre') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $nombre }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Apellido') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $apellido }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Dirección') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->direccion }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Referencia') }}</span>
                                <p class="mt-1 font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $user->locker_code }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('País') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->pais }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Estado / Departamento') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->estado }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Ciudad') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->ciudad }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Teléfono') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->telefono }}</p>
                            </div>

                            <div>
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Código Postal') }}</span>
                                <p class="mt-1 text-gray-900 dark:text-gray-100">{{ $method->codigo_postal }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
