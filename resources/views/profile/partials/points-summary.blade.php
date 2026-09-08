<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Puntos de fidelidad') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Acumulas puntos con cada envío facturado y puedes canjearlos por descuentos.') }}
        </p>
    </header>

    <div class="mt-4 flex items-center gap-4">
        <div class="flex flex-col items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 px-8 py-6 min-w-[140px]">
            <span class="text-5xl font-bold text-blue-700 dark:text-blue-300">{{ $user->total_points }}</span>
            <span class="mt-1 text-sm text-blue-600 dark:text-blue-400 font-medium">puntos acumulados</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 max-w-xs">
            <p>Ganas <strong>1 punto por cada $1</strong> del costo de servicio de tus envíos facturados.</p>
            <p class="mt-2">Cada <strong>10 puntos equivalen a ₡10</strong> de descuento en colones.</p>
        </div>
    </div>

    @if ($user->hasRedeemablePoints())
        <div class="mt-4">
            @if ($user->redeem_points_requested)
                <div class="flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-100 dark:border-green-800 px-4 py-3">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        {{ __('Tus :points puntos se canjearán (₡:discount de descuento) en tu próxima factura.', ['points' => $user->loyalty_points, 'discount' => number_format($user->loyalty_points, 2)]) }}
                    </p>
                    <form method="POST" action="{{ route('profile.redeem-points') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="text-sm font-medium text-red-600 dark:text-red-400 hover:underline whitespace-nowrap">
                            {{ __('Cancelar canje') }}
                        </button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('profile.redeem-points') }}">
                    @csrf
                    @method('PATCH')
                    <x-primary-button type="submit">
                        {{ __('Canjear puntos en la siguiente factura') }}
                    </x-primary-button>
                </form>
            @endif
        </div>
    @endif
</section>
