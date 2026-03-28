@extends('layouts.guest')

@section('title', 'Login - SmartSpace')

@section('content')
<div class="login-shell min-h-screen flex items-center justify-center px-4 py-10 overflow-hidden relative">
    <div class="pointer-events-none absolute inset-0">
        <div class="login-led-orb login-led-orb-a"></div>
        <div class="login-led-orb login-led-orb-b"></div>
        <div class="login-led-grid"></div>
    </div>

    <div class="w-full max-w-5xl relative z-10">
        <div class="flex items-center justify-center mb-8">
            <div class="login-brand-wrap">
                <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="h-40 sm:h-44 md:h-48 w-auto max-w-none login-brand-logo" onerror="this.onerror=null;this.src='{{ asset('images/smartspace-logo.svg') }}';">
            </div>
        </div>

        {{-- Only show login errors for login attempts --}}
        @if ($errors->has('login'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800">{{ $errors->first('login') }}</p>
            </div>
        @endif

        {{-- Signup error modal --}}
        <div x-data="{ showSignupError: false }" x-init="@if($errors->any() && !$errors->has('login')) showSignupError = true @endif" x-show="showSignupError" x-cloak style="z-index: 1000; position: fixed; inset: 0;">
            <div class="absolute inset-0 backdrop-blur-sm z-10"></div>
            <div class="absolute inset-0 flex items-center justify-center z-20">
                <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full">
                    <h2 class="text-lg font-bold text-red-700 mb-2">Please fix the following:</h2>
                    <ul class="list-disc pl-5 text-sm text-red-700 space-y-1 mb-4">
                        @foreach ($errors->all() as $error)
                            @if ($error !== $errors->first('login'))
                                <li>{{ $error }}</li>
                            @endif
                        @endforeach
                    </ul>
                    <button @click="showSignupError = false" class="mt-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold">Close</button>
                </div>
            </div>
        </div>

        @php
            $signupFields = [
                'name',
                'email',
                'phone_number',
                'user_type',
                'employee_category',
                'course',
                'qcid_number',
                'sex',
                'civil_status',
                'date_of_birth',
                'date_issued',
                'valid_until',
                'address',
                'ocr_text',
                'qcid_image',
                'password',
                'password_confirmation',
            ];

            $hasSignupOldInput = collect($signupFields)
                ->contains(fn ($field) => filled(old($field)));

            $openSignupOnLoad = $hasSignupOldInput || $errors->hasAny($signupFields);
        @endphp

        <div x-data="signupLoginApp({{ $openSignupOnLoad ? 'true' : 'false' }})">
            <div class="flex justify-center">
                <!-- User Login -->
                <div class="w-full max-w-xl">
                    <div class="relative group login-card-wrap">
                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-12 -bottom-14 h-24 bg-gradient-to-r from-cyan-400 via-indigo-500 to-teal-400 blur-3xl opacity-45 transition-opacity duration-300 group-hover:opacity-80"></div>
                        <div class="login-neon-card bg-gradient-to-b from-white/95 to-slate-50/95 backdrop-blur rounded-3xl border border-white/70 shadow-[0_24px_80px_-24px_rgba(15,23,42,0.55)] overflow-hidden transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_32px_90px_-28px_rgba(30,41,59,0.65)]">
                    <div class="px-6 py-5 border-b border-gray-100 bg-white/60">
                        <h2 class="text-lg font-bold text-gray-900">Login</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <form method="POST" action="{{ route('login.post') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Email or Username</label>
                                <input name="login" type="text" value="{{ old('login') }}" required autocomplete="username"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Password</label>
                                <div class="relative mt-1">
                                    <input name="password" :type="showLoginPassword ? 'text' : 'password'" required autocomplete="current-password"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <button type="button"
                                            @click="showLoginPassword = !showLoginPassword"
                                            :aria-label="showLoginPassword ? 'Hide password' : 'Show password'"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                        <i x-show="!showLoginPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                        <i x-show="showLoginPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                    </button>
                                </div>
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

                        <a href="{{ route('register') }}" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors shadow-lg shadow-teal-700/20">
                            Sign Up
                        </a>

                        <!-- Install App Button (PWA) -->
                        <div x-data="pwaInstallPrompt()" x-show="showInstall" x-cloak class="mt-4">
                            <button type="button"
                                    @click="installPwa"
                                    class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-500 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Install App
                            </button>
                        </div>

                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sign Up Modal -->
            <div x-show="signupOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-8">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="signupOpen = false"></div>
                <div class="relative mx-auto w-full max-w-6xl overflow-hidden rounded-3xl border border-indigo-100 bg-slate-50 shadow-[0_30px_100px_-30px_rgba(30,41,59,0.75)]">
                    <div class="signup-hero px-6 py-6 sm:px-8 bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 border-b border-indigo-200/20">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="inline-flex rounded-full bg-indigo-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100 border border-indigo-300/20">User Verification Portal</p>
                                <h3 class="mt-3 text-3xl font-extrabold tracking-tight text-white">Create your SmartSpace account</h3>
                                <p class="mt-2 max-w-2xl text-sm text-indigo-100">Upload your Quezon City Citizen ID, review captured details, and finish signup in one guided flow.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[430px]">
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Current status</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.isVerified ? 'Ready to submit' : (scan.idAssessment === 'Fake QC ID' ? 'Fake QC ID' : (scan.idAssessment === 'INVALID' ? 'Invalid ID' : 'Not submitted'))"></p>
                                </div>
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Detected ID</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.idAssessment || (signup.ocr_text ? 'Scanning...' : 'Not verified')"></p>
                                </div>
                                <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Confidence</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.confidenceLabel || '—'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="max-h-[calc(92vh-120px)] overflow-y-auto signup-scroll-area p-5 sm:p-6">
                        @include('auth.partials.qc-signup-form', ['signupStandalone' => false])
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script type="text/javascript">
// Blade: Hide signupOldInput and signupQcidVerifyUrl from page output
window.signupOldInput = {
    name: @json(old('name', '')),
    user_type: @json(old('user_type', '')),
    employee_category: @json(old('employee_category', '')),
    course: @json(old('course', '')),
    qcid_number: @json(old('qcid_number', '')),
    sex: @json(old('sex', '')),
    civil_status: @json(old('civil_status', '')),
    date_of_birth: @json(old('date_of_birth', '')),
    date_issued: @json(old('date_issued', '')),
    valid_until: @json(old('valid_until', '')),
    address: @json(old('address', '')),
    ocr_text: @json(old('ocr_text', '')),
};
window.signupQcidVerifyUrl = @json(route('signup.qcid.verify'));
</script>
<script>
    document.addEventListener('keydown', function (e) {
        const key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && key === 'k') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('toggle-admin-login'));
        }
    });
</script>
<script>
function pwaInstallPrompt() {
    return {
        showInstall: false,
        deferredPrompt: null,
        isMobile: /android|iphone|ipad|ipod|opera mini|iemobile|mobile/i.test(navigator.userAgent),
        init() {
            if (!this.isMobile) return;
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.deferredPrompt = e;
                this.showInstall = true;
            });
            window.addEventListener('appinstalled', () => {
                this.showInstall = false;
            });
        },
        installPwa() {
            if (this.deferredPrompt) {
                this.deferredPrompt.prompt();
                this.deferredPrompt.userChoice.then((choiceResult) => {
                    if (choiceResult.outcome === 'accepted') {
                        this.showInstall = false;
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
