<x-guest-layout :wide="true">
    <form method="POST" action="{{ route('register') }}" x-data="locationSelector()">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full " type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Phone -->
        <div>
            <x-input-label for="phone" :value="__('Teléfono')" />
            <x-text-input id="phone" class="block mt-1 w-full" type="tel" name="phone" :value="old('phone')" required placeholder="+1 (555) 000-0000" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <!-- Cedula -->
        <div>
            <x-input-label for="cedula" :value="__('Cédula')" />
            <x-text-input id="cedula" class="block mt-1 w-full" type="text" name="cedula" :value="old('cedula')" required placeholder="000-0000000-0" />
            <x-input-error :messages="$errors->get('cedula')" class="mt-2" />
        </div>

        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Provincia -->
        <div>
            <x-input-label for="provincia_id" :value="__('Provincia')" />
            <select id="provincia_id" name="provincia_id" x-model="provinciaId" @change="loadCantones" required class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="">Seleccione una provincia</option>
                <template x-for="provincia in provincias" :key="provincia.id">
                    <option :value="provincia.id" x-text="provincia.nombre"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('provincia_id')" class="mt-2" />
        </div>

        <!-- Canton -->
        <div>
            <x-input-label for="canton_id" :value="__('Cantón')" />
            <select id="canton_id" name="canton_id" x-model="cantonId" @change="loadDistritos" :disabled="!provinciaId" required class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                <option value="">Seleccione un cantón</option>
                <template x-for="canton in cantones" :key="canton.id">
                    <option :value="canton.id" x-text="canton.nombre"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('canton_id')" class="mt-2" />
        </div>

        <!-- Distrito -->
        <div>
            <x-input-label for="distrito_id" :value="__('Distrito')" />
            <select id="distrito_id" name="distrito_id" x-model="distritoId" :disabled="!cantonId" required class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                <option value="">Seleccione un distrito</option>
                <template x-for="distrito in distritos" :key="distrito.id">
                    <option :value="distrito.id" x-text="distrito.nombre"></option>
                </template>
            </select>
            <x-input-error :messages="$errors->get('distrito_id')" class="mt-2" />
        </div>
        </div>

        <!-- Address -->
        <div class="mt-4">
            <x-input-label for="address" :value="__('Dirección Exacta')" />
            <textarea id="address" name="address" rows="3" required placeholder="Ingrese su dirección completa" class="block mt-1 w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address') }}</textarea>
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
        </div>

        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
        </div>
    </form>

    <script>
        function locationSelector() {
            return {
                provinciaId: parseInt('{{ old("provincia_id") }}') || '',
                cantonId: parseInt('{{ old("canton_id") }}') || '',
                distritoId: parseInt('{{ old("distrito_id") }}') || '',
                provincias: [],
                cantones: [],
                distritos: [],

                async init() {
                    await this.loadProvincias();
                    if (this.provinciaId) {
                        await this.loadCantones();
                        if (this.cantonId) {
                            await this.loadDistritos();
                        }
                    }
                },

                async loadProvincias() {
                    const response = await fetch('/api/provincias');
                    this.provincias = await response.json();
                },

                async loadCantones() {
                    this.cantonId = '';
                    this.distritoId = '';
                    this.cantones = [];
                    this.distritos = [];
                    
                    if (!this.provinciaId) return;
                    
                    const response = await fetch(`/api/provincias/${this.provinciaId}/cantones`);
                    this.cantones = await response.json();
                },

                async loadDistritos() {
                    this.distritoId = '';
                    this.distritos = [];
                    
                    if (!this.cantonId) return;
                    
                    const response = await fetch(`/api/cantones/${this.cantonId}/distritos`);
                    this.distritos = await response.json();
                }
            }
        }
    </script>
</x-guest-layout>
