<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Crewly') }}</title>
        {{-- Apply the saved light/dark choice before the page paints (no flash of the wrong colours). See themeSlice.js. --}}
        <script>
            (function () {
                try {
                    var mode = localStorage.getItem('crewly-theme');
                    var dark = mode === 'dark' || (mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                    document.documentElement.dataset.theme = dark ? 'dark' : 'light';
                } catch (e) {}
            })();
        </script>
        <link rel="icon" href="/logo/crewly-favicon.svg" type="image/svg+xml">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        {{-- The React single-page app. All data comes from the /api endpoints. --}}
        @viteReactRefresh
        @vite(['resources/js/spa/main.jsx'])
    </head>
    <body class="font-sans antialiased">
        <div id="root"></div>
    </body>
</html>
