@extends('layouts.guest')

@section('title', 'Login | SmartSpace')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-5xl">
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg shadow-indigo-500/30">
                    <i class="fa-solid fa-book-open"></i>
                </div>
                <div>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">SmartSpace</h1>
                    {{-- <p class="text-sm text-gray-500">Collaborative Room Booking System for QCU Library</p> --}}
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800">Please fix the following:</p>
                <ul class="mt-2 list-disc pl-5 text-sm text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div
            x-data="{ adminOpen: {{ $errors->has('admin_username') ? 'true' : 'false' }}, signupOpen: {{ (old('name') || $errors->has('name') || $errors->has('password') || $errors->has('password_confirmation')) ? 'true' : 'false' }} }"
            x-on:toggle-admin-login.window="adminOpen = !adminOpen; if (adminOpen) { signupOpen = false; } $nextTick(() => adminOpen && $refs.adminUsername && $refs.adminUsername.focus())"
        >
            <div class="flex justify-center" x-show="!adminOpen" x-cloak>
                <!-- User Login -->
                <div class="w-full max-w-xl">
                    <div class="relative group">
                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-10 -bottom-10 h-16 bg-gradient-to-r from-indigo-500 via-purple-500 to-teal-500 blur-3xl opacity-30 transition-opacity duration-300 group-hover:opacity-60"></div>
                        <div class="bg-gradient-to-b from-white to-slate-50 rounded-3xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-2xl">
                    <div class="px-6 py-5 border-b border-gray-100 bg-white/60">
                        <h2 class="text-lg font-bold text-gray-900">User Login</h2>
                        <p class="text-sm text-gray-500 mt-1">Login with Google or with your email.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <form method="POST" action="{{ route('login.post') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Password</label>
                                <input name="password" type="password" required autocomplete="current-password"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div class="flex items-center">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Remember me
                                </label>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                                Login
                            </button>
                        </form>

                        <button type="button" @click="signupOpen = true" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors">
                            Sign Up
                        </button>

                        <a href="{{ route('google.redirect') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 hover:bg-gray-50 text-sm font-semibold text-gray-800 transition-colors">
                            <span class="w-5 h-5 inline-flex items-center justify-center rounded-full border border-gray-300 bg-white text-xs font-extrabold text-gray-900">G</span>
                            Continue with Google
                        </a>

                        <div id="installPromptContainer" class="hidden">
                            <button id="installBtn" type="button" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-colors">
                                <span>Install App</span>
                            </button>
                        </div>

                        <p id="desktopInstallHint" class="hidden rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700">
                            Use your browser install menu, or open SmartSpace on your phone to install.
                        </p>

                        <div id="iosInstallHint" class="hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm text-blue-800">
                            Tap Share -> Add to Home Screen
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sign Up Modal -->
            <div x-show="signupOpen" x-cloak class="modal p-4" :class="{ 'modal-open': signupOpen }" @keydown.escape.window="signupOpen = false">
                <div class="modal-box w-11/12 max-w-xl p-0 bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden" @click.stop>
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Create Account</h3>
                            <p class="text-sm text-gray-500 mt-1">Sign up with your email.</p>
                        </div>
                        <button type="button" @click="signupOpen = false" class="px-3 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                            Close
                        </button>
                    </div>
                    <div class="p-6">
                        <form method="POST" action="{{ route('register.post') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Name</label>
                                <input name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Email</label>
                                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Password</label>
                                <input name="password" type="password" required autocomplete="new-password"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Confirm Password</label>
                                <input name="password_confirmation" type="password" required autocomplete="new-password"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors">
                                Create Account
                            </button>
                        </form>
                    </div>
                </div>
                <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="signupOpen = false">close</button>
            </div>

            <!-- Admin Login Modal (Ctrl+K) -->
            <div x-show="adminOpen" x-cloak class="modal p-4" :class="{ 'modal-open': adminOpen }" @keydown.escape.window="adminOpen = false">
                <div class="modal-box w-11/12 max-w-xl p-0 bg-transparent border-0 shadow-none overflow-visible" @click.stop>
                    <div class="relative group">
                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-10 -bottom-10 h-16 bg-gradient-to-r from-indigo-500 via-purple-500 to-teal-500 blur-3xl opacity-40"></div>
                        <div class="bg-gradient-to-b from-white to-slate-50 rounded-3xl border border-gray-200 shadow-2xl overflow-hidden">
                            <div class="px-6 py-5 border-b border-gray-100 bg-white/60 flex items-center justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Admin Login</h2>
                                    <p class="text-sm text-gray-500 mt-1">Enter your admin username and password.</p>
                                </div>
                                <button type="button" @click="adminOpen = false" class="px-3 py-2 rounded-xl text-sm font-semibold text-gray-600 hover:bg-gray-100">
                                    Close
                                </button>
                            </div>

                            <div class="p-6 space-y-4">
                                <form method="POST" action="{{ route('admin.login') }}" class="space-y-3">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Username</label>
                                        <input x-ref="adminUsername" name="admin_username" type="text" value="{{ old('admin_username') }}" required autocomplete="username"
                                               class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700">Password</label>
                                        <input name="admin_password" type="password" required autocomplete="current-password"
                                               class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    </div>
                                    <div class="flex items-center">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            Remember me
                                        </label>
                                    </div>
                                    <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                                        Login
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="adminOpen = false">close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let deferredInstallPrompt;
    const isMobileBrowser = /android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini/i.test(window.navigator.userAgent);
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const iosInstallHint = document.getElementById('iosInstallHint');
    const desktopInstallHint = document.getElementById('desktopInstallHint');
    const installBtn = document.getElementById('installBtn');
    const installContainer = document.getElementById('installPromptContainer');
    const installToast = document.getElementById('installToast');

    const showInstallToast = (message) => {
        if (!installToast) {
            return;
        }

        installToast.textContent = message;
        installToast.classList.remove('hidden', 'opacity-0', 'translate-y-3');

        window.setTimeout(() => {
            installToast.classList.add('opacity-0', 'translate-y-3');
            window.setTimeout(() => {
                installToast.classList.add('hidden');
            }, 250);
        }, 2600);
    };

    const markAppInstalled = () => {
        try {
            window.localStorage.setItem('smartspace_pwa_installed', '1');
        } catch (error) {
            console.warn('Unable to persist install state', error);
        }
    };

    const isAppInstalled = () => {
        if (isStandalone) {
            return true;
        }

        try {
            return window.localStorage.getItem('smartspace_pwa_installed') === '1';
        } catch (error) {
            console.warn('Unable to read install state', error);
            return false;
        }
    };

    const updateInstallUiState = () => {
        const installed = isAppInstalled();
        const canShowInstallButton = isMobileBrowser && !installed;
        const shouldShowDesktopHint = !isMobileBrowser && !installed;

        if (installContainer) {
            installContainer.classList.toggle('hidden', !canShowInstallButton);
        }

        if (desktopInstallHint) {
            desktopInstallHint.classList.toggle('hidden', !shouldShowDesktopHint);
        }
    };

    updateInstallUiState();

    if (isIos && !isStandalone && iosInstallHint) {
        iosInstallHint.classList.remove('hidden');
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        updateInstallUiState();
    });

    installBtn?.addEventListener('click', async () => {
        if (!deferredInstallPrompt) {
            if (!isMobileBrowser) {
                showInstallToast('Use your browser install menu, or open SmartSpace on your phone to install.');
            } else {
                showInstallToast('Use your browser install menu to add SmartSpace to home screen.');
            }

            if (isIos && iosInstallHint) {
                iosInstallHint.classList.remove('hidden');
            }

            return;
        }

        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;

        if (outcome === 'accepted') {
            markAppInstalled();
            updateInstallUiState();
            showInstallToast('SmartSpace app installed successfully.');
        }

        deferredInstallPrompt = null;
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        markAppInstalled();
        updateInstallUiState();

        if (iosInstallHint) {
            iosInstallHint.classList.add('hidden');
        }

        showInstallToast('SmartSpace app installed successfully.');
    });

    document.addEventListener('keydown', function (e) {
        const key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && key === 'k') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('toggle-admin-login'));
        }
    });
</script>

<div id="installToast" class="hidden fixed left-1/2 bottom-6 z-50 w-[90%] max-w-sm -translate-x-1/2 rounded-xl bg-emerald-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-lg opacity-0 translate-y-3 transition-all duration-200"></div>
@endpush
@endsection
