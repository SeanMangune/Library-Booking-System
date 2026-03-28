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
            <div class="premium-logo-container">
                <img src="/images/smartspace-logo.png" alt="SmartSpace" class="h-44 sm:h-48 md:h-56 lg:h-64 w-auto max-w-none logo-premium logo-glow-purple">
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

                        <button type="button" @click="signupOpen = true" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors shadow-lg shadow-teal-700/20">
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
            <div x-show="signupOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-8">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="signupOpen = false"></div>
                <div class="relative mx-auto w-full max-w-6xl overflow-hidden rounded-3xl border border-indigo-100 bg-slate-50 shadow-[0_30px_100px_-30px_rgba(30,41,59,0.75)]">
                    <div class="signup-hero px-6 py-6 sm:px-8 border-b border-white/10 shadow-2xl">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div class="relative z-10">
                                <div class="flex flex-wrap items-center gap-3 mb-4">
                                    <span class="inline-flex rounded-lg bg-indigo-500/20 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-indigo-200 backdrop-blur-md ring-1 ring-white/20">
                                        QC Citizen Verification
                                    </span>
                                    <span class="signup-badge-glow inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-[10px] font-bold text-emerald-300 backdrop-blur-md">
                                        <i class="fa-solid fa-shield-halved text-[9px]"></i>
                                        SECURE SERVER-SIDE SCANNING
                                    </span>
                                </div>
                                <h3 class="text-4xl font-black tracking-tight text-white">Create your SmartSpace account</h3>
                                <p class="mt-2 max-w-2xl text-base text-indigo-100/80 font-medium">Upload your Quezon City Citizen ID for instant verification and auto-fill.</p>
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
                        <form method="POST" action="{{ route('register.post') }}" enctype="multipart/form-data" class="space-y-5">
                            @csrf
                            <input type="hidden" name="ocr_text" x-model="signup.ocr_text">


                                <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                                <div class="space-y-5 lg:col-span-2">
                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <h4 class="text-xl font-bold text-slate-900">Upload QC ID</h4>
                                                <p class="text-sm text-slate-500">Use a clear image for OCR scanning and auto-fill.</p>
                                            </div>
                                            <button type="button"
                                                    @click="scanSignupQcId()"
                                                    :disabled="scan.isProcessing"
                                                    class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60"
                                                    x-text="scan.isProcessing ? 'Scanning...' : 'Re-read QC ID'"></button>
                                        </div>

                                        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div class="rounded-2xl border-2 border-dashed border-indigo-200 bg-indigo-50/40 p-4">
                                                <input type="file"
                                                       name="qcid_image"
                                                       accept="image/png,image/jpeg,image/jpg,image/webp"
                                                       @click="$event.target.value=''"
                                                       @change="onSignupQcImageChange($event)"
                                                       required
                                                       class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-teal-700">
                                                <p class="mt-3 text-xs text-slate-500">Accepted: JPG, PNG, WEBP up to 25 MB</p>

                                                <div x-show="scan.previewUrl" x-cloak class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white">
                                                    <img :src="scan.previewUrl" alt="QC ID preview" class="h-40 w-full object-cover">
                                                </div>
                                            </div>

                                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-center justify-between">
                                                    <p class="text-sm font-semibold text-slate-800">Verification snapshot</p>
                                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700" x-show="!scan.status" x-cloak>Waiting</span>
                                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700" x-show="scan.status" x-cloak>Captured</span>
                                                </div>
                                                <dl class="mt-3 space-y-2 text-sm">
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Cardholder</dt><dd class="font-medium text-slate-800" x-text="signup.name || '—'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">QC ID number</dt><dd class="font-medium text-slate-800" x-text="signup.qcid_number || '—'"></dd></div>
                                                    <div class="flex items-center justify-between gap-3"><dt class="text-slate-500">Birth date</dt><dd class="font-medium text-slate-800" x-text="signup.date_of_birth || '—'"></dd></div>
                                                </dl>
                                            </div>
                                        </div>

                                        <div x-show="scan.error" x-cloak class="mt-3 rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700" x-text="scan.error"></div>
                                        <!-- Show progress/status except for redundant fake/invalid messages -->
                                        <div x-show="scan.status && scan.status !== 'Fake QC ID detected.' && scan.status !== 'Invalid ID detected.'" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" x-text="scan.status"></div>
                                    </section>

                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
                                        <h4 class="text-xl font-bold text-slate-900">Registration details</h4>
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Full Name</label>
                                                <input name="name" type="text" value="{{ old('name') }}" x-model="signup.name" required autocomplete="name"
                                                       maxlength="20"
                                                       @input="signup.name = signup.name.replace(/[0-9]/g, '').substring(0, 20)"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                <p class="mt-1 text-xs text-slate-400" x-text="signup.name.length + '/20 characters'"></p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Email</label>
                                                <input name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Mobile Number</label>
                                                <input name="phone_number" type="text" value="{{ old('phone_number') }}" required autocomplete="tel" placeholder="09xxxxxxxxx"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">QC ID Number</label>
                                                <input name="qcid_number" type="text" value="{{ old('qcid_number') }}" x-model="signup.qcid_number" required placeholder="### ### ########"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Account Type</label>
                                                <select name="user_type" x-model="signup.user_type" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select account type</option>
                                                    <option value="student" @selected(old('user_type') === 'student')>Student</option>
                                                    <option value="employee" @selected(old('user_type') === 'employee')>Employee</option>
                                                    <option value="alumni" @selected(old('user_type') === 'alumni')>Alumni</option>
                                                </select>
                                            </div>
                                            <div x-show="signup.user_type === 'employee'" x-cloak>
                                                <label class="block text-sm font-semibold text-slate-700">Employee Category</label>
                                                <select name="employee_category" x-model="signup.employee_category" :required="signup.user_type === 'employee'" :disabled="signup.user_type !== 'employee'"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select employee category</option>
                                                    <option value="professor" @selected(old('employee_category') === 'professor')>Professor / Faculty</option>
                                                    <option value="academic_staff" @selected(old('employee_category') === 'academic_staff')>Academic Staff</option>
                                                    <option value="administrative_staff" @selected(old('employee_category') === 'administrative_staff')>Administrative Staff</option>
                                                    <option value="it_personnel" @selected(old('employee_category') === 'it_personnel')>IT Personnel</option>
                                                    <option value="registrar_personnel" @selected(old('employee_category') === 'registrar_personnel')>Registrar Personnel</option>
                                                    <option value="guidance_personnel" @selected(old('employee_category') === 'guidance_personnel')>Guidance Personnel</option>
                                                    <option value="security_personnel" @selected(old('employee_category') === 'security_personnel')>Security Personnel</option>
                                                    <option value="maintenance_personnel" @selected(old('employee_category') === 'maintenance_personnel')>Maintenance Personnel</option>
                                                    <option value="other" @selected(old('employee_category') === 'other')>Other</option>
                                                </select>
                                            </div>
                                            <div x-show="signup.user_type === 'student'" x-cloak>
                                                <label class="block text-sm font-semibold text-slate-700">Course / Department</label>
                                                <select name="course" x-model="signup.course" :required="signup.user_type === 'student'" :disabled="signup.user_type !== 'student'"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select course or department</option>
                                                    <option value="BSIT" @selected(old('course') === 'BSIT')>BSIT</option>
                                                    <option value="BSIE" @selected(old('course') === 'BSIE')>BSIE</option>
                                                    <option value="BSENT" @selected(old('course') === 'BSENT')>BSENT</option>
                                                    <option value="BSCS" @selected(old('course') === 'BSCS')>BSCS</option>
                                                    <option value="BSCPE" @selected(old('course') === 'BSCPE')>BSCPE</option>
                                                    <option value="BSED" @selected(old('course') === 'BSED')>BSED</option>
                                                    <option value="BEED" @selected(old('course') === 'BEED')>BEED</option>
                                                    <option value="BSOA" @selected(old('course') === 'BSOA')>BSOA</option>
                                                    <option value="BSA" @selected(old('course') === 'BSA')>BSA</option>
                                                    <option value="BSBA" @selected(old('course') === 'BSBA')>BSBA</option>
                                                    <option value="OTHER" @selected(old('course') === 'OTHER')>OTHER</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Sex</label>
                                                <select name="sex" x-model="signup.sex"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select sex</option>
                                                    <option value="MALE" @selected(old('sex') === 'MALE')>Male</option>
                                                    <option value="FEMALE" @selected(old('sex') === 'FEMALE')>Female</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Civil Status</label>
                                                <select name="civil_status" x-model="signup.civil_status"
                                                        class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <option value="">Select civil status</option>
                                                    <option value="SINGLE" @selected(old('civil_status') === 'SINGLE')>Single</option>
                                                    <option value="MARRIED" @selected(old('civil_status') === 'MARRIED')>Married</option>
                                                    <option value="WIDOWED" @selected(old('civil_status') === 'WIDOWED')>Widowed</option>
                                                    <option value="DIVORCED" @selected(old('civil_status') === 'DIVORCED')>Divorced</option>
                                                    <option value="SEPARATED" @selected(old('civil_status') === 'SEPARATED')>Separated</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Date of Birth</label>
                                                <input name="date_of_birth" type="date" value="{{ old('date_of_birth') }}" x-model="signup.date_of_birth"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Date Issued</label>
                                                <input name="date_issued" type="date" value="{{ old('date_issued') }}" x-model="signup.date_issued"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Valid Until</label>
                                                <input name="valid_until" type="date" value="{{ old('valid_until') }}" x-model="signup.valid_until"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-semibold text-slate-700">Address</label>
                                                <textarea name="address" rows="2" x-model="signup.address" maxlength="100"
                                                          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address') }}</textarea>
                                                <p class="mt-1 text-xs text-slate-400" x-text="(signup.address || '').length + '/100 characters'"></p>
                                            </div>
                                            <div class="md:col-span-2" x-data="{
                                                get hasMin() { return signupPassword.length >= 15 },
                                                get hasUpper() { return /[A-Z]/.test(signupPassword) },
                                                get hasNumber() { return /\d/.test(signupPassword) },
                                                get strength() {
                                                    let s = 0;
                                                    if (this.hasMin) s++;
                                                    if (this.hasUpper) s++;
                                                    if (this.hasNumber) s++;
                                                    return s;
                                                }
                                            }">
                                                <label class="block text-sm font-semibold text-slate-700">Password</label>
                                                <div class="relative mt-1">
                                                    <input name="password" :type="showSignupPassword ? 'text' : 'password'" required autocomplete="new-password"
                                                           x-model="signupPassword" minlength="15" maxlength="50"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                           :class="signupPassword.length > 0 && signupPassword.length < 15 ? 'border-red-300 focus:ring-red-400' : ''">
                                                    <button type="button"
                                                            @click="showSignupPassword = !showSignupPassword"
                                                            :aria-label="showSignupPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
                                                </div>
                                                <!-- Password Requirements Checklist -->
                                                <div x-show="signupPassword.length > 0" x-cloak x-transition class="mt-2 space-y-1">
                                                    <div class="flex items-center gap-2 text-xs transition-colors duration-200"
                                                         :class="signupPassword.length >= 15 ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none"
                                                           :class="signupPassword.length >= 15 ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>Minimum 15 characters</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs" :class="hasUpper ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none" :class="hasUpper ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>At least one uppercase letter</span>
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs" :class="hasNumber ? 'text-emerald-700' : 'text-slate-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none" :class="hasNumber ? 'fa-solid fa-circle-check' : 'fa-regular fa-circle'"></i>
                                                        <span>At least one number</span>
                                                    </div>
                                                    <div class="mt-1">
                                                        <span class="text-xs font-semibold" :class="{
                                                            'text-rose-600': strength === 1,
                                                            'text-amber-600': strength === 2,
                                                            'text-emerald-700': strength === 3
                                                        }">
                                                            <template x-if="strength === 1">Weak</template>
                                                            <template x-if="strength === 2">Medium</template>
                                                            <template x-if="strength === 3">Strong</template>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="md:col-span-2">
                                                <label class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                                                <div class="relative mt-1">
                                                    <input name="password_confirmation" :type="showSignupConfirmPassword ? 'text' : 'password'" required autocomplete="new-password"
                                                           x-model="signupConfirmPassword" minlength="15" maxlength="50"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                           :class="signupConfirmPassword.length > 0 && signupConfirmPassword !== signupPassword ? 'border-red-300 focus:ring-red-400' : ''">
                                                    <button type="button"
                                                            @click="showSignupConfirmPassword = !showSignupConfirmPassword"
                                                            :aria-label="showSignupConfirmPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
                                                </div>
                                                <!-- Password Match Warning -->
                                                <div x-show="signupConfirmPassword.length > 0" x-cloak x-transition class="mt-2">
                                                    <div class="flex items-center gap-2 text-xs transition-colors duration-200"
                                                         :class="signupConfirmPassword === signupPassword ? 'text-emerald-600' : 'text-red-500'">
                                                        <i class="w-3.5 h-3.5 fa-icon text-sm leading-none"
                                                           :class="signupConfirmPassword === signupPassword ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-xmark'"></i>
                                                        <span x-text="signupConfirmPassword === signupPassword ? 'Passwords match' : 'Passwords do not match'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-3 pt-2">
                                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-teal-700">
                                                Create Account
                                            </button>
                                            <button type="button" @click="signupOpen = false" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">
                                                Close
                                            </button>
                                        </div>
                                    </section>
                                </div>

                                <aside class="space-y-4">
                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                        <h4 class="text-xl font-bold text-slate-900">How verification works</h4>
                                        <ol class="mt-3 space-y-3 text-sm text-slate-600">
                                            <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 font-bold text-indigo-700">1</span><span>Upload your QC ID image.</span></li>
                                            <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-violet-100 font-bold text-violet-700">2</span><span>Scan and review captured details.</span></li>
                                            <li class="flex gap-3"><span class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 font-bold text-emerald-700">3</span><span>Submit your account for instant portal access.</span></li>
                                        </ol>
                                    </section>

                                    <section class="rounded-2xl border border-indigo-900/40 bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 p-4 text-white shadow-lg">
                                        <h4 class="text-xl font-bold">Registration status</h4>
                                        <p class="text-sm text-indigo-200">Live signup progress</p>
                                        <dl class="mt-4 space-y-2 text-sm">
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">Image</dt><dd class="font-semibold" x-text="scan.previewUrl ? 'Uploaded' : 'Waiting'"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">OCR</dt><dd class="font-semibold" x-text="scan.isVerified ? 'Captured' : (scan.idAssessment ? 'Rejected' : 'Not captured')"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">QC ID number</dt><dd class="font-semibold" x-text="signup.qcid_number || '—'"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">Ready</dt><dd class="font-semibold" x-text="(scan.isVerified && signup.name && signup.qcid_number && signup.ocr_text) ? 'Yes' : 'No'"></dd></div>
                                        </dl>
                                    </section>
                                </aside>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</script>

</script>
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
function signupLoginApp(initialSignupOpen) {
    return {
        signupOpen: !!initialSignupOpen,
        showLoginPassword: false,
        showSignupPassword: false,
        showSignupConfirmPassword: false,
        signupPassword: '',
        signupConfirmPassword: '',
        signup: { ...window.signupOldInput },
        scan: {
            file: null,
            previewUrl: '',
            isProcessing: false,
            error: '',
            status: '',
            idAssessment: '',
            confidenceLabel: '',
            isVerified: false,
        },

        init() {
            // Copy old input values into signup object on Alpine.js init
            this.signup = { ...this.signupOldInput };
        },

        onSignupQcImageChange(event) {
            const file = event.target?.files?.[0] || null;
            this.scan.file = file;
            this.scan.error = '';
            this.scan.status = '';
            this.scan.idAssessment = '';
            this.scan.confidenceLabel = '';
            this.scan.isVerified = false;

            if (!file) {
                this.scan.previewUrl = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.scan.error = 'Please upload an image file for QC ID scanning.';
                this.scan.file = null;
                this.scan.previewUrl = '';
                return;
            }

            if (this.scan.previewUrl) {
                URL.revokeObjectURL(this.scan.previewUrl);
            }
            this.scan.previewUrl = URL.createObjectURL(file);

            // Auto-start scanning immediately after upload selection.
            this.scanSignupQcId();
        },

        normalizeDate(raw) {
            if (!raw) return '';
            
            // Clean up the string: replace all separators with a single space
            const cleaned = String(raw).toUpperCase().replace(/[^A-Z0-9]/g, ' ').replace(/\s+/g, ' ').trim();
            
            // Try standard Date parsing first for things like "JAN 01 1990"
            const dateObj = new Date(cleaned);
            if (!isNaN(dateObj.getTime())) {
                return dateObj.toISOString().split('T')[0];
            }

            // Fallback for DD MM YYYY or MM DD YYYY
            const parts = cleaned.split(' ');
            if (parts.length === 3) {
                let day, month, year;
                if (parts[2].length === 4) { // YYYY is at the end
                    year = parts[2];
                    if (parseInt(parts[0]) > 12) { // Definitely DD MM YYYY
                        day = parts[0];
                        month = parts[1];
                    } else { // Ambiguous, assume MM DD YYYY or standard format
                        month = parts[0];
                        day = parts[1];
                    }
                } else if (parts[0].length === 4) { // YYYY is at the start
                    year = parts[0];
                    month = parts[1];
                    day = parts[2];
                }

                if (year && month && day) {
                    // Normalize to YYYY-MM-DD
                    const m = String(month).padStart(2, '0');
                    const d = String(day).padStart(2, '0');
                    return `${year}-${m}-${d}`;
                }
            }

            return '';
        },

        async getBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        },

        parseDateCandidates(text) {
            const source = String(text || '').toUpperCase();
            const normalized = source
                .replace(/[|]/g, '/')
                .replace(/\s+/g, ' ')
                .trim();

            const results = [];
            const pushDate = (raw) => {
                const parsed = this.normalizeDate(raw);
                if (parsed && !results.includes(parsed)) {
                    results.push(parsed);
                }
            };

            const yyyySlash = normalized.match(/\b\d{4}\/\d{1,2}\/\d{1,2}\b/g) || [];
            yyyySlash.forEach(pushDate);

            const mmddyyyy = normalized.match(/\b\d{1,2}\/\d{1,2}\/\d{4}\b/g) || [];
            mmddyyyy.forEach(pushDate);

            const yyyymmdd = normalized.match(/\b\d{8}\b/g) || [];
            yyyymmdd.forEach((digits) => pushDate(`${digits.slice(0, 4)}/${digits.slice(4, 6)}/${digits.slice(6, 8)}`));

            return results;
        },

        improveAddress(value) {
            let address = String(value || '')
                .toUpperCase()
                .replace(/\s+/g, ' ')
                .trim();

            if (!address) {
                return '';
            }

            address = address
                .replace(/\b(?:DATE ISSUED|VALID UNTIL|DATE OF BIRTH|DOB|CIVIL STATUS|SEX|SIGNATURE)\b/g, ' ')
                .replace(/\b\d{4}\/\d{1,2}\/\d{1,2}\b/g, ' ')
                .replace(/\b\d{1,2}\/\d{1,2}\/\d{4}\b/g, ' ')
                .replace(/\b(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)\b\s+\d{1,2}\b/g, ' ')
                .replace(/\b\d{1,2}\s+(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)\b/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            address = address
                .replace(/^\d{1,2}\s+[A-Z]{3,10}\s+(?=\d{1,4}\s+[A-Z])/, '')
                .replace(/^\d{1,2}\s+(?=\d{1,4}\s+[A-Z])/, '')
                .trim();

            const streetAnchor = address.match(/((?:\d{1,4}[A-Z\-]?\s+[A-Z][A-Z0-9\s,.\-]{6,})QUEZON\s+CITY)/);
            if (streetAnchor?.[1]) {
                address = streetAnchor[1];
            }

            const qcChunk = address.match(/([A-Z0-9,\-.\s]{8,}?QUEZON\s+CITY)/);
            if (qcChunk?.[1]) {
                address = qcChunk[1];
            }

            return address.replace(/\s+,/g, ',').replace(/,{2,}/g, ',').replace(/\s{2,}/g, ' ').trim();
        },

        digitsOnly(value) {
            return String(value || '').replace(/\D/g, '');
        },

        normalizeDigitLike(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[OQDP]/g, '0')
                .replace(/[IL]/g, '1')
                .replace(/Z/g, '2')
                .replace(/S/g, '5')
                .replace(/B/g, '8')
                .replace(/G/g, '6');
        },

        formatQcIdDigits(digits) {
            const only = this.digitsOnly(digits);
            if (only.length === 12) return `00${only}`;
            if (only.length === 13) return `0${only}`;
            if (only.length === 14) return only;
            return '';
        },

        extractQcIdCandidatesFromText(text) {
            const source = this.normalizeDigitLike(text);
            const raw = [
                ...(source.match(/\b\d{3}\s*\d{3}\s*\d{6,8}\b/g) || []),
                ...(source.match(/\b\d{12,14}\b/g) || []),
                ...(source.match(/\b\d{3}\D{0,4}\d{3}\D{0,4}\d{6,8}\b/g) || []),
            ];

            const candidates = [];
            for (const item of raw) {
                const normalized = this.formatQcIdDigits(item);
                if (!normalized) continue;
                candidates.push(`${normalized.slice(0, 3)} ${normalized.slice(3, 6)} ${normalized.slice(6, 14)}`);
            }

            return candidates;
        },

        chooseBestQcIdCandidate(initialValue, verificationText, ocrText) {
            const all = [
                ...this.extractQcIdCandidatesFromText(initialValue || ''),
                ...this.extractQcIdCandidatesFromText(verificationText || ''),
                ...this.extractQcIdCandidatesFromText(ocrText || ''),
            ];

            if (all.length === 0) return '';

            const counts = new Map();
            for (const candidate of all) {
                counts.set(candidate, (counts.get(candidate) || 0) + 1);
            }

            const ranked = [...counts.entries()].sort((a, b) => b[1] - a[1]);
            const top = ranked[0]?.[0] || '';
            if (!top) return '';

            const topDigits = this.digitsOnly(top);
            for (const [candidate] of ranked) {
                const digits = this.digitsOnly(candidate);
                if (digits.length !== 14 || topDigits.length !== 14 || digits === topDigits) continue;

                let diffCount = 0;
                let diffIndex = -1;
                for (let i = 0; i < 14; i += 1) {
                    if (digits[i] !== topDigits[i]) {
                        diffCount += 1;
                        diffIndex = i;
                    }
                }

                if (diffCount === 1 && [6, 7].includes(diffIndex)) {
                    const a = topDigits[diffIndex];
                    const b = digits[diffIndex];
                    if ((a === '0' || a === '1') && /[3689]/.test(b)) return top;
                    if ((b === '0' || b === '1') && /[3689]/.test(a)) return candidate;
                }
            }

            return top;
        },

        applyDateFallbacks(verification, ocrText) {
            const currentYear = new Date().getFullYear();
            const candidates = this.parseDateCandidates(`${verification?.normalized_text || ''}\n${ocrText || ''}`);

            const getYear = (value) => Number(String(value || '').slice(0, 4));
            const isBirthYear = (year) => year >= 1940 && year <= 2015;
            const isIssuedYear = (year) => year >= 2015 && year <= currentYear;
            const isValidYear = (year) => year > currentYear;

            const existingDob = this.normalizeDate(verification?.date_of_birth || this.signup.date_of_birth);
            const existingIssued = this.normalizeDate(verification?.date_issued || this.signup.date_issued);
            const existingValid = this.normalizeDate(verification?.valid_until || this.signup.valid_until);

            let dob = existingDob;
            let issued = existingIssued;
            let valid = existingValid;

            for (const date of candidates) {
                const year = getYear(date);
                if (!dob && isBirthYear(year)) {
                    dob = date;
                    continue;
                }
                if (!issued && isIssuedYear(year)) {
                    issued = date;
                    continue;
                }
                if (!valid && isValidYear(year)) {
                    valid = date;
                }
            }

            if (!issued || !valid) {
                const sorted = [...candidates].sort();
                if (!issued) {
                    issued = sorted.find((date) => isIssuedYear(getYear(date))) || issued || '';
                }
                if (!valid) {
                    valid = sorted.find((date) => isValidYear(getYear(date))) || valid || '';
                }
            }

            return { dob, issued, valid };
        },

        async scanSignupQcId() {
            this.scan.error = '';
            this.scan.status = '';

            if (!this.scan.file) {
                this.scan.error = 'Upload your QC ID image first.';
                return;
            }

            this.scan.isProcessing = true;
            this.scan.status = 'Reading QC ID image...';
            this.scan.idAssessment = '';
            this.scan.confidenceLabel = '';
            this.scan.isVerified = false;

            try {
                const base64Image = await this.getBase64(this.scan.file);

                const formData = new FormData();
                formData.append('ocr_text', ocrText);
                formData.append('user_name', this.signup.name || '');
                formData.append('qcid_image', this.scan.file);

                const response = await fetch(window.signupQcidVerifyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });

                const payload = await response.json();
                const verification = payload?.verification || {};
                const assessment = verification?.id_assessment || (payload?.success ? 'Verified' : 'INVALID');
                const isVerified = payload?.success && assessment === 'Verified';
                this.scan.idAssessment = assessment;
                this.scan.isVerified = !!isVerified;
                const confidenceScore = Number(verification?.confidence_score || 0);
                this.scan.confidenceLabel = isVerified
                    ? `${Math.max(0, Math.min(100, confidenceScore))}%`
                    : '—';

                if (!isVerified) {
                    this.signup.ocr_text = '';
                    this.signup.qcid_number = '';
                    this.signup.sex = '';
                    this.signup.civil_status = '';
                    this.signup.date_of_birth = '';
                    this.signup.date_issued = '';
                    this.signup.valid_until = '';
                    this.signup.address = '';

                    this.scan.error = this.scan.idAssessment === 'Fake QC ID'
                        ? 'This ID is FAKE QC ID.'
                        : 'This ID is INVALID.';
                    this.scan.status = this.scan.idAssessment === 'Fake QC ID' ? 'Fake QC ID detected.' : 'Invalid ID detected.';
                } else {
                    this.signup.ocr_text = payload?.ocr_text || '';
                    this.scan.status = 'QC ID verified and fields auto-filled. Please review before creating account.';

                    if (verification.cardholder_name) {
                        this.signup.name = verification.cardholder_name;
                    }
                    const correctedId = this.chooseBestQcIdCandidate(
                        verification.id_number || '',
                        verification.normalized_text || '',
                        this.signup.ocr_text || '',
                    );
                    this.signup.qcid_number = correctedId || verification.id_number || '';

                    if (verification.sex) {
                        this.signup.sex = verification.sex;
                    }
                    if (verification.civil_status) {
                        this.signup.civil_status = verification.civil_status;
                    }
                    if (verification.date_of_birth) {
                        const normalized = this.normalizeDate(verification.date_of_birth);
                        if (normalized) {
                            this.signup.date_of_birth = normalized;
                        }
                    }
                    if (verification.date_issued) {
                        const normalized = this.normalizeDate(verification.date_issued);
                        if (normalized) {
                            this.signup.date_issued = normalized;
                        }
                    }
                    if (verification.valid_until) {
                        const normalized = this.normalizeDate(verification.valid_until);
                        if (normalized) {
                            this.signup.valid_until = normalized;
                        }
                    }
                    if (verification.address) {
                        this.signup.address = this.improveAddress(verification.address);
                    }

                    const fallbackDates = this.applyDateFallbacks(verification, this.signup.ocr_text);
                    if (!this.signup.date_of_birth && fallbackDates.dob) {
                        this.signup.date_of_birth = fallbackDates.dob;
                    }
                    if (!this.signup.date_issued && fallbackDates.issued) {
                        this.signup.date_issued = fallbackDates.issued;
                    }
                    if (!this.signup.valid_until && fallbackDates.valid) {
                        this.signup.valid_until = fallbackDates.valid;
                    }

                    if (this.signup.address) {
                        this.signup.address = this.improveAddress(this.signup.address);
                    } else if (verification.normalized_text) {
                        this.signup.address = this.improveAddress(verification.normalized_text);
                    }
                }
            } catch (error) {
                this.scan.error = error?.message || 'Unable to scan the QC ID image right now.';
            } finally {
                this.scan.isProcessing = false;
            }
        },
    };
}
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

@push('styles')
<style>
    .login-shell {
        background:
            radial-gradient(1200px 500px at 18% 10%, rgba(14, 165, 233, 0.14), transparent 60%),
            radial-gradient(900px 420px at 85% 75%, rgba(79, 70, 229, 0.18), transparent 65%),
            linear-gradient(135deg, #eef4ff 0%, #f6f8fc 45%, #ecfbf8 100%);
    }

    .login-led-orb {
        position: absolute;
        border-radius: 999px;
        filter: blur(10px);
        opacity: 0.55;
        animation: pulseGlow 6s ease-in-out infinite;
    }

    .login-led-orb-a {
        width: 320px;
        height: 320px;
        left: -90px;
        top: 45px;
        background: radial-gradient(circle at 35% 35%, rgba(6, 182, 212, 0.82), rgba(59, 130, 246, 0.2) 68%, transparent 72%);
    }

    .login-led-orb-b {
        width: 380px;
        height: 380px;
        right: -120px;
        bottom: -90px;
        animation-delay: 1.25s;
        background: radial-gradient(circle at 40% 40%, rgba(99, 102, 241, 0.8), rgba(45, 212, 191, 0.2) 70%, transparent 74%);
    }

    .login-led-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(99, 102, 241, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(20, 184, 166, 0.05) 1px, transparent 1px);
        background-size: 28px 28px;
        mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.72) 12%, transparent 80%);
        -webkit-mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.72) 12%, transparent 80%);
    }

    .login-card-wrap {
        animation: floatCard 8s ease-in-out infinite, loginCardEntrance 0.6s ease-out both;
    }

    @keyframes loginCardEntrance {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.97);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes loginBrandEntrance {
        from {
            opacity: 0;
            transform: translateY(-16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-brand-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 1rem;
        animation: loginBrandEntrance 0.5s ease-out both;
    }

    .login-brand-wrap::before {
        content: '';
        position: absolute;
        inset: -20px -35px;
        border-radius: 999px;
        background: radial-gradient(circle at 30% 40%, rgba(56, 189, 248, 0.35), rgba(79, 70, 229, 0.22) 45%, rgba(20, 184, 166, 0.2) 75%, transparent 100%);
        filter: blur(14px);
        animation: pulseGlow 5.5s ease-in-out infinite;
    }

    .login-brand-logo {
        position: relative;
        filter: drop-shadow(0 10px 18px rgba(76, 82, 235, 0.28));
    }

    .login-neon-card {
        position: relative;
    }

    .login-neon-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1.5rem;
        padding: 1px;
        background: linear-gradient(120deg, rgba(59, 130, 246, 0.35), rgba(45, 212, 191, 0.28), rgba(99, 102, 241, 0.35));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    .signup-scroll-area {
        scrollbar-width: thin;
        scrollbar-color: #99a6ff #eef2ff;
    }

    .signup-scroll-area::-webkit-scrollbar {
        width: 8px;
    }

    .signup-scroll-area::-webkit-scrollbar-track {
        background: #eef2ff;
        border-radius: 999px;
    }

    .signup-scroll-area::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #6366f1, #14b8a6);
        border-radius: 999px;
    }

    @keyframes pulseGlow {
        0%, 100% {
            opacity: 0.48;
            transform: scale(1);
        }
        50% {
            opacity: 0.72;
            transform: scale(1.08);
        }
    }

    @keyframes floatCard {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-4px);
        }
    }

    @media (max-width: 768px) {
        .login-led-orb-a {
            width: 230px;
            height: 230px;
            left: -80px;
            top: 80px;
        }

        .login-led-orb-b {
            width: 260px;
            height: 260px;
            right: -90px;
            bottom: -80px;
        }
    }
</style>
@endpush
@endsection
