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
                                <span class="font-medium text-gray-500 dark:text-gray-400">{{ __('Dirección 2/ Unidad/ Suite/ Casillero') }}</span>
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

                        @if(filled($method->direccion_alternativa))
                            <div class="mt-4 rounded-lg bg-amber-50 dark:bg-amber-900/30 p-4 text-sm">
                                <h4 class="flex items-center gap-2 font-medium text-gray-900 dark:text-gray-100">
                                    <svg class="h-4 w-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" />
                                    </svg>
                                    {{ __('Dirección Alternativa / Alternative Address') }}
                                </h4>
                                <p class="mt-2 whitespace-pre-line break-words text-gray-700 dark:text-gray-200">{{ $method->direccion_alternativa }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
