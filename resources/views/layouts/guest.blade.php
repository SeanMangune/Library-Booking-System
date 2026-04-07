<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <link rel="icon" type="image/png" href="{{ asset('images/smartspace-logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/smartspace-logo.png') }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartSpace')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/persist@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Inter', system-ui, sans-serif; }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-gray-50 to-slate-100 text-gray-900 antialiased">
    @include('components.ui.page-loader')

    @yield('content')
    <div id="swUpdateToast" class="hidden fixed left-1/2 bottom-6 z-50 w-[92%] max-w-md -translate-x-1/2 rounded-xl border border-blue-200 bg-white px-4 py-3 text-sm shadow-xl">
        <div class="flex items-center justify-between gap-3">
            <p class="text-slate-700">A new SmartSpace version is ready.</p>
            <button id="swUpdateBtn" type="button" class="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
                Refresh
            </button>
        </div>
    </div>

    <script>
    if ('serviceWorker' in navigator) {
        let swRegistration;
        let refreshing = false;
        const updateToast = document.getElementById('swUpdateToast');
        const updateBtn = document.getElementById('swUpdateBtn');

        const showUpdateToast = function () {
            if (updateToast) {
                updateToast.classList.remove('hidden');
            }
        };

        const bindUpdateFlow = function (registration) {
            if (!registration) {
                return;
            }

            if (registration.waiting) {
                showUpdateToast();
            }

            registration.addEventListener('updatefound', function () {
                const newWorker = registration.installing;
                if (!newWorker) {
                    return;
                }

                newWorker.addEventListener('statechange', function () {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        showUpdateToast();
                    }
                });
            });
        };

        navigator.serviceWorker.addEventListener('controllerchange', function () {
            if (refreshing) {
                return;
            }
            refreshing = true;
            window.location.reload();
        });

        updateBtn?.addEventListener('click', function () {
            if (swRegistration && swRegistration.waiting) {
                swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        });

        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js')
                .then(function (registration) {
                    swRegistration = registration;
                    bindUpdateFlow(registration);
                    window.setInterval(function () {
                        registration.update();
                    }, 60 * 60 * 1000);
                })
                .catch(function(error) {
                    console.error('Service worker registration failed', error);
                });
        });
    }
    </script>
    @stack('scripts')
</body>
</html>
