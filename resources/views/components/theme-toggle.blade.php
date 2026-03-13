<button
    @click="toggleTheme()"
    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
    :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
>
    {{-- Luna: visible en modo claro (click para ir a oscuro) --}}
    <svg x-show="!isDark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
    </svg>

    {{-- Sol: visible en modo oscuro (click para ir a claro) --}}
    <svg x-show="isDark" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round">
        <circle cx="12" cy="12" r="4" fill="currentColor" stroke="none"/>
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
    </svg>
</button>
