<button
    @click="toggleTheme()"
    class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
    :title="isDark ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'"
>
    <svg x-show="!isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
    </svg>
    <svg x-show="isDark" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zM4.22 4.22a1 1 0 011.414 0l.707.707a1 1 0 00-1.414-1.414l-.707.707zm11.314 1.414a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM4 10a1 1 0 01-1-1V8a1 1 0 012 0v1a1 1 0 01-1 1zm0 7a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm7 4a1 1 0 11-2 0v-1a1 1 0 112 0v1zM9.464 16.536a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zm2.828-2.828a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM20 10a1 1 0 01-1-1V8a1 1 0 012 0v1a1 1 0 01-1 1zM4.22 15.78a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414l.707.707z" clip-rule="evenodd"></path>
    </svg>
</button>
