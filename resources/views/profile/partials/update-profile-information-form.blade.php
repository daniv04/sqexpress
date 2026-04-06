<section>
    <header class="relative mb-6">
        <div class="flex justify-between items-start gap-6">
            <div class="flex-1">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {{ __('Información del Perfil') }}
                </h2>

                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __("Actualiza la información de tu cuenta y la dirección de correo electrónico.") }}
                </p>
            </div>
            
            <!-- Locker Code - Top Right Corner -->
            <div class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg p-4 w-48 flex-shrink-0">
                <p class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">{{ __('Tu Casillero') }}</p>
                <p class="text-2xl font-bold text-indigo-900 dark:text-indigo-100 font-mono mt-2">
                    {{ $user->locker_code ?? 'No asignado' }}
                </p>
                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-2">
                    Asignado por el sistema
                </p>
            </div>
        </div>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6" x-data="locationSelector()" x-init="init()">
        @csrf
        @method('patch')

        <!-- Name & Email - 2 Columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <x-input-label for="name" :value="__('Nombre')" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
                <x-input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div>
                <x-input-label for="email" :value="__('Correo Electrónico')" />
                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('email')" />
            </div>
        </div>

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mb-6">
                <p class="text-sm text-gray-800 dark:text-gray-200">
                    {{ __('Tu correo electrónico no está verificado.') }}

                    <button form="send-verification" class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800">
                        {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 font-medium text-sm text-green-600 dark:text-green-400">
                        {{ __('Se ha enviado un nuevo enlace de verificación a tu correo.') }}
                    </p>
                @endif
            </div>
        @endif

        <!-- Phone & Cedula - 2 Columns -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <x-input-label for="phone" :value="__('Teléfono')" />
                <x-text-input id="phone" name="phone" type="tel" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('phone')" />
            </div>

            <div>
                <x-input-label for="cedula" :value="__('Cédula')" />
                <x-text-input id="cedula" name="cedula" type="text" class="mt-1 block w-full" :value="old('cedula', $user->cedula)" autocomplete="cedula" />
                <x-input-error class="mt-2" :messages="$errors->get('cedula')" />
            </div>
        </div>

        <!-- Current Location Display - 3 Columns (Read-only) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700">
            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Provincia Guardada') }}</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->provincia->nombre ?? __('No asignada') }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Cantón Guardado') }}</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->canton->nombre ?? __('No asignado') }}</p>
            </div>

            <div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Distrito Guardado') }}</p>
                <p class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $user->distrito->nombre ?? __('No asignado') }}</p>
            </div>
        </div>

        <!-- Provincia, Canton, Distrito - 3 Columns -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <x-input-label for="provincia_id" :value="__('Provincia')" />
                <select id="provincia_id" name="provincia_id"
                        x-model="provinciaId"
                        @change="loadCantones()"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                    <option value="">Seleccione una provincia</option>
                    @foreach($provincias as $provincia)
                        <option value="{{ $provincia->id }}">{{ $provincia->nombre }}</option>
                    @endforeach
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('provincia_id')" />
            </div>

            <div>
                <x-input-label for="canton_id" :value="__('Cantón')" />
                <select id="canton_id" name="canton_id"
                        x-model="cantonId"
                        @change="loadDistritos()"
                        :disabled="!provinciaId"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                    <option value="">Seleccione un cantón</option>
                    <template x-for="canton in cantones" :key="canton.id">
                        <option :value="canton.id" x-text="canton.nombre"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('canton_id')" />
            </div>

            <div>
                <x-input-label for="distrito_id" :value="__('Distrito')" />
                <select id="distrito_id" name="distrito_id"
                        x-model="distritoId"
                        :disabled="!cantonId"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm disabled:opacity-50">
                    <option value="">Seleccione un distrito</option>
                    <template x-for="distrito in distritos" :key="distrito.id">
                        <option :value="distrito.id" x-text="distrito.nombre"></option>
                    </template>
                </select>
                <x-input-error class="mt-2" :messages="$errors->get('distrito_id')" />
            </div>
        </div>

        <!-- Address -->
        <div class="mb-6">
            <x-input-label for="address" :value="__('Dirección Exacta')" />
            <textarea id="address" name="address" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">{{ old('address', $user->address) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('address')" />
        </div>

        <!-- Save Button -->
        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>

    <script>
        function locationSelector() {
            return {
                provinciaId: '{{ old("provincia_id", $user->provincia_id ?? "") }}',
                cantonId: '{{ old("canton_id", $user->canton_id ?? "") }}',
                distritoId: '{{ old("distrito_id", $user->distrito_id ?? "") }}',
                cantones: [],
                distritos: [],

                async init() {
                    const savedCanton = this.cantonId;
                    const savedDistrito = this.distritoId;

                    if (this.provinciaId) {
                        const res = await fetch(`/api/provincias/${this.provinciaId}/cantones`);
                        this.cantones = await res.json();
                        this.cantonId = savedCanton;
                    }
                    if (this.cantonId) {
                        const res = await fetch(`/api/cantones/${this.cantonId}/distritos`);
                        this.distritos = await res.json();
                        this.distritoId = savedDistrito;
                    }
                },

                async loadCantones() {
                    this.cantonId = '';
                    this.distritoId = '';
                    this.cantones = [];
                    this.distritos = [];
                    if (!this.provinciaId) return;
                    const res = await fetch(`/api/provincias/${this.provinciaId}/cantones`);
                    this.cantones = await res.json();
                },

                async loadDistritos() {
                    this.distritoId = '';
                    this.distritos = [];
                    if (!this.cantonId) return;
                    const res = await fetch(`/api/cantones/${this.cantonId}/distritos`);
                    this.distritos = await res.json();
                },
            }
        }
    </script>
</section>
