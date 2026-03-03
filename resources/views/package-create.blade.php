<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Prealerta de Paquete') }}
            </h2>
            <a href="{{ route('mis-paquetes') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                        Crear Nueva Prealerta
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        Complete el formulario para prealerta su paquete que viene en camino.
                    </p>

                    <form action="{{ route('package.store') }}" method="POST" class="space-y-6">
                        @csrf

                        <!-- Método de Envío -->
                        <div>
                            <label for="shipping_method_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Método de Envío <span class="text-red-500">*</span>
                            </label>
                            <select name="shipping_method_id"
                                    id="shipping_method_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Seleccionar método de envío...</option>
                                @foreach($shippingMethods as $method)
                                    <option value="{{ $method->id }}" {{ old('shipping_method_id') == $method->id ? 'selected' : '' }}>
                                        {{ $method->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('shipping_method_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Número de Tracking -->
                        <div>
                            <label for="tracking" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Número de Tracking <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="tracking"
                                   id="tracking"
                                   value="{{ old('tracking') }}"
                                   required
                                   placeholder="Ej: 1234567890"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Ingrese el número de tracking del transportista
                            </p>
                            @error('tracking')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Descripción -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Descripción del Contenido
                            </label>
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      placeholder="Describa brevemente el contenido del paquete (ej: ropa, electrónicos, etc.)"
                                      class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description') }}</textarea>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Máximo 1000 caracteres
                            </p>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Peso -->
                            <div>
                                <label for="weight" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Peso (kg)
                                </label>
                                <input type="number"
                                       name="weight"
                                       id="weight"
                                       step="0.01"
                                       min="0.01"
                                       value="{{ old('weight') }}"
                                       placeholder="0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Peso aproximado del paquete
                                </p>
                                @error('weight')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Valor Aproximado -->
                            <div>
                                <label for="approx_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Valor Aproximado ($)
                                </label>
                                <input type="number"
                                       name="approx_value"
                                       id="approx_value"
                                       step="0.01"
                                       min="0.01"
                                       value="{{ old('approx_value') }}"
                                       placeholder="0.00"
                                       class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Valor aproximado declarado del paquete
                                </p>
                                @error('approx_value')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Info Box -->
                        <div class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 8a1 1 0 000 2h6a1 1 0 100-2H8zm0 4a1 1 0 000 2H8a1 1 0 000-2z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">
                                        Una vez completada la prealerta, podrá ver el estado de su paquete en su panel de control.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                            <a href="{{ route('mis-paquetes') }}" class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-sm text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition">
                                Cancelar
                            </a>
                            <button type="submit" class="inline-flex items-center px-6 py-2 bg-indigo-600 dark:bg-indigo-700 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                Crear Prealerta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
