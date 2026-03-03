<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6" x-data="locationSelector()">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="locker_code" :value="__('Número de Casillero')" />
            <div class="mt-1 px-4 py-2 bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 font-mono">
                    {{ $user->locker_code ?? 'No asignado' }}
                </p>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Este es tu número de casillero asignado por el sistema.
            </p>
        </div>

        <div>
            <x-input-label for="cedula" :value="__('Cédula')" />
            <x-text-input id="cedula" name="cedula" type="text" class="mt-1 block w-full" :value="old('cedula', $user->cedula)" autocomplete="cedula" />
            <x-input-error class="mt-2" :messages="$errors->get('cedula')" />
        </div>
        
        <!-- Provincia -->
        <div>
            <x-input-label for="provincia_id" :value="__('Provincia')" />
            <select id="provincia_id" name="provincia_id" x-model="provinciaId" @change="loadCantones" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                <option value="">Seleccione una provincia</option>
                <template x-for="provincia in provincias" :key="provincia.id">
                    <option :value="provincia.id" x-text="provincia.nombre"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('provincia_id')" />
        </div>

        <!-- Canton -->
        <div>
            <x-input-label for="canton_id" :value="__('Cantón')" />
            <select id="canton_id" name="canton_id" x-model="cantonId" @change="loadDistritos" :disabled="!provinciaId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                <option value="">Seleccione un cantón</option>
                <template x-for="canton in cantones" :key="canton.id">
                    <option :value="canton.id" x-text="canton.nombre"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('canton_id')" />
        </div>

        <!-- Distrito -->
        <div>
            <x-input-label for="distrito_id" :value="__('Distrito')" />
            <select id="distrito_id" name="distrito_id" x-model="distritoId" :disabled="!cantonId" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                <option value="">Seleccione un distrito</option>
                <template x-for="distrito in distritos" :key="distrito.id">
                    <option :value="distrito.id" x-text="distrito.nombre"></option>
                </template>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('distrito_id')" />
        </div>

        <div>
            <x-input-label for="address" :value="__('Dirección Exacta')" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address', $user->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>

    <script>
        function locationSelector() {
            return {
                provinciaId: parseInt('{{ old("provincia_id", $user->provincia_id ?? 0) }}') || '',
                cantonId: parseInt('{{ old("canton_id", $user->canton_id ?? 0) }}') || '',
                distritoId: parseInt('{{ old("distrito_id", $user->distrito_id ?? 0) }}') || '',
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
                    
                    const savedCantonId = parseInt('{{ old("canton_id", $user->canton_id ?? 0) }}');
                    if (savedCantonId && this.provinciaId === parseInt('{{ old("provincia_id", $user->provincia_id ?? 0) }}')) {
                        this.cantonId = savedCantonId;
                    }
                },

                async loadDistritos() {
                    this.distritoId = '';
                    this.distritos = [];
                    
                    if (!this.cantonId) return;
                    
                    const response = await fetch(`/api/cantones/${this.cantonId}/distritos`);
                    this.distritos = await response.json();
                    
                    const savedDistritoId = parseInt('{{ old("distrito_id", $user->distrito_id ?? 0) }}');
                    if (savedDistritoId && this.cantonId === parseInt('{{ old("canton_id", $user->canton_id ?? 0) }}')) {
                        this.distritoId = savedDistritoId;
                    }
                }
            }
        }
    </script>
</section>
