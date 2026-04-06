<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Puntos de fidelidad') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Acumulas puntos con cada envío facturado. Próximamente podrás canjearlos por descuentos.') }}
        </p>
    </header>

    <div class="mt-4 flex items-center gap-4">
        <div class="flex flex-col items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 px-8 py-6 min-w-[140px]">
            <span class="text-5xl font-bold text-blue-700 dark:text-blue-300">{{ $user->total_points }}</span>
            <span class="mt-1 text-sm text-blue-600 dark:text-blue-400 font-medium">puntos acumulados</span>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400 max-w-xs">
            <p>Ganas <strong>1 punto por cada ₡100</strong> del costo de servicio de tus envíos facturados.</p>
            <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">El canje de puntos estará disponible próximamente.</p>
        </div>
    </div>
</section>
