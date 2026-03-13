<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeToggle()" @load="init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Anti-FOUC: apply dark class before Alpine initializes -->
        <script>
            if (localStorage.theme === 'dark' ||
                (!localStorage.theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            function themeToggle() {
                return {
                    isDark: localStorage.getItem('theme') === 'dark' || 
                            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),
                    toggleTheme() {
                        this.isDark = !this.isDark;
                        localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
                        document.documentElement.classList.toggle('dark', this.isDark);
                        document.dispatchEvent(new CustomEvent('theme-changed', { detail: { isDark: this.isDark } }));
                    },
                    init() {
                        document.documentElement.classList.toggle('dark', this.isDark);
                    }
                }
            }
        </script>
    </head>
    <body class="font-sans antialiased" :class="{ 'dark': isDark }">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900" :class="{ 'dark': isDark }">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                @if (session('success'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 3500)"
                        x-show="show"
                        x-transition
                        class="fixed top-5 right-5 z-50"
                        style="display: none;"
                    >
                        <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-md shadow-lg dark:bg-green-900/30 dark:border-green-700 dark:text-green-200">
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        x-data="{ show: true }"
                        x-init="setTimeout(() => show = false, 3500)"
                        x-show="show"
                        x-transition
                        class="fixed top-5 right-5 z-50"
                        style="display: none;"
                    >
                        <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-md shadow-lg dark:bg-red-900/30 dark:border-red-700 dark:text-red-200">
                            {{ session('error') }}
                        </div>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </body>
</html>
