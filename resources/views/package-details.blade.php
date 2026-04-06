<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Detalles del Paquete') }}
            </h2>
            <div class="flex items-center space-x-3">
                @if($package->status === 'prealerted')
                    <!-- Botón Editar -->
                    <a href="{{ route('package.edit', $package) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar
                    </a>

                    <!-- Botón Eliminar -->
                    <form action="{{ route('package.destroy', $package) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este paquete?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 dark:bg-red-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                @endif

                <a href="{{ route('mis-paquetes') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Volver
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Información General -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Información General</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tracking -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Número de Tracking
                            </label>
                            <p class="text-lg font-mono font-semibold text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded">
                                {{ $package->tracking }}
                            </p>
                        </div>

                        <!-- Estado Actual -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Estado Actual
                            </label>
                            <div class="mt-1">
                                <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full
                                    @if($package->status === 'delivered') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($package->status === 'ready_to_deliver') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @elseif($package->status === 'canceled') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($package->status === 'prealerted') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                    @endif">
                                    {{ $statuses[$package->status] ?? ucfirst(str_replace('_', ' ', $package->status)) }}
                                </span>
                            </div>
                        </div>

                        <!-- Método de Envío -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Método de Envío
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                {{ $package->shippingMethod->name }}
                            </p>
                        </div>

                        <!-- Fecha de Prealerta -->
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Fecha de Prealerta
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                @if($package->prealerted_at)
                                    {{ $package->prealerted_at->format('d/m/Y H:i') }}
                                @else
                                    {{ $package->created_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </div>

                        <!-- Peso -->
                        @if($package->weight)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Peso
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                {{ number_format($package->weight, 2) }} kg
                            </p>
                        </div>
                        @endif

                        <!-- Valor Aproximado -->
                        @if($package->approx_value)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Valor Aproximado
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                ${{ number_format($package->approx_value, 2) }}
                            </p>
                        </div>
                        @endif

                        <!-- Ubicación en Estante -->
                        @if($package->shelf_location)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Ubicación en Estante
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                {{ $package->shelf_location }}
                            </p>
                        </div>
                        @endif

                        <!-- Descripción -->
                        @if($package->description)
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">
                                Descripción
                            </label>
                            <p class="text-base text-gray-900 dark:text-gray-100">
                                {{ $package->description }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Historial de Estados -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">Historial de Estados</h3>
                    
                    @if($package->statusHistories->count() > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($package->statusHistories->sortByDesc('created_at') as $index => $history)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                            @endif
                                            
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-800
                                                        @if($history->to_status === 'delivered') bg-green-500
                                                        @elseif($history->to_status === 'ready_to_deliver') bg-blue-500
                                                        @elseif($history->to_status === 'canceled') bg-red-500
                                                        @elseif($history->to_status === 'prealerted') bg-gray-400
                                                        @else bg-yellow-500
                                                        @endif">
                                                        @if($history->to_status === 'delivered')
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @else
                                                            <svg class="h-5 w-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        @endif
                                                    </span>
                                                </div>
                                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                                    <div>
                                                        <p class="text-sm text-gray-900 dark:text-gray-100">
                                                            @if($history->from_status)
                                                                Cambió de <span class="font-medium">{{ $statuses[$history->from_status] ?? $history->from_status }}</span> 
                                                                a <span class="font-medium">{{ $statuses[$history->to_status] ?? $history->to_status }}</span>
                                                            @else
                                                                Estado inicial: <span class="font-medium">{{ $statuses[$history->to_status] ?? $history->to_status }}</span>
                                                            @endif
                                                        </p>
                                                        @if($history->note)
                                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                                {{ $history->note }}
                                                            </p>
                                                        @endif
                                                        @if($history->changedBy)
                                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                Por: {{ $history->changedBy->name }}
                                                            </p>
                                                        @else
                                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                                Por: Sistema
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="whitespace-nowrap text-right text-sm text-gray-500 dark:text-gray-400">
                                                        <time datetime="{{ $history->created_at->toIso8601String() }}">
                                                            {{ $history->created_at->format('d/m/Y') }}<br>
                                                            {{ $history->created_at->format('H:i') }}
                                                        </time>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                            No hay historial de estados disponible.
                        </p>
                    @endif
                </div>
            </div>

        </div>
    </div>


</x-app-layout>
