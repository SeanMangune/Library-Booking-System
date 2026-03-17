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

        <div x-data="signupLoginApp({{ (old('name') || old('phone_number') || old('user_type') || old('course') || old('qcid_number') || old('ocr_text') || $errors->any()) ? 'true' : 'false' }})">
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

                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sign Up Modal -->
            <div x-show="signupOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto px-4 py-8">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="signupOpen = false"></div>
                <div class="relative mx-auto w-full max-w-6xl overflow-hidden rounded-3xl border border-indigo-100 bg-slate-50 shadow-[0_30px_100px_-30px_rgba(30,41,59,0.75)]">
                    <div class="signup-hero px-6 py-6 sm:px-8">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <p class="inline-flex rounded-full bg-white/20 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">User Verification Portal</p>
                                <h3 class="mt-3 text-3xl font-extrabold tracking-tight text-white">Create your SmartSpace account</h3>
                                <p class="mt-2 max-w-2xl text-sm text-indigo-100/95">Upload your Quezon City Citizen ID, review captured details, and finish signup in one guided flow.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[430px]">
                                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Current status</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="signup.ocr_text ? 'Ready to submit' : 'Not submitted'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Detected ID</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="signup.ocr_text ? 'QC Citizen ID' : 'Not verified'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/20 bg-white/10 px-3 py-3 backdrop-blur-sm">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Confidence</p>
                                    <p class="mt-1 text-lg font-bold text-white" x-text="scan.status ? 'High' : '—'"></p>
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
                                        <div x-show="scan.status" x-cloak class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700" x-text="scan.status"></div>
                                    </section>

                                    <section class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 shadow-sm space-y-4">
                                        <h4 class="text-xl font-bold text-slate-900">Registration details</h4>
                                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Full Name</label>
                                                <input name="name" type="text" value="{{ old('name') }}" x-model="signup.name" required autocomplete="name"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                                                <input name="sex" type="text" value="{{ old('sex') }}" x-model="signup.sex"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Civil Status</label>
                                                <input name="civil_status" type="text" value="{{ old('civil_status') }}" x-model="signup.civil_status"
                                                       class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
                                                <textarea name="address" rows="2" x-model="signup.address"
                                                          class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('address') }}</textarea>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Password</label>
                                                <div class="relative mt-1">
                                                    <input name="password" :type="showSignupPassword ? 'text' : 'password'" required autocomplete="new-password"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <button type="button"
                                                            @click="showSignupPassword = !showSignupPassword"
                                                            :aria-label="showSignupPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-slate-700">Confirm Password</label>
                                                <div class="relative mt-1">
                                                    <input name="password_confirmation" :type="showSignupConfirmPassword ? 'text' : 'password'" required autocomplete="new-password"
                                                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                                    <button type="button"
                                                            @click="showSignupConfirmPassword = !showSignupConfirmPassword"
                                                            :aria-label="showSignupConfirmPassword ? 'Hide password' : 'Show password'"
                                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700">
                                                        <i x-show="!showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                                        <i x-show="showSignupConfirmPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                                    </button>
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
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">OCR</dt><dd class="font-semibold" x-text="signup.ocr_text ? 'Captured' : 'Not captured'"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">QC ID number</dt><dd class="font-semibold" x-text="signup.qcid_number || '—'"></dd></div>
                                            <div class="flex items-center justify-between"><dt class="text-indigo-200">Ready</dt><dd class="font-semibold" x-text="(signup.name && signup.qcid_number && signup.ocr_text) ? 'Yes' : 'No'"></dd></div>
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
function signupLoginApp(initialSignupOpen) {
    return {
        signupOpen: !!initialSignupOpen,
        showLoginPassword: false,
        showSignupPassword: false,
        showSignupConfirmPassword: false,
        signup: {
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
        },
        scan: {
            file: null,
            previewUrl: '',
            isProcessing: false,
            error: '',
            status: '',
        },

        onSignupQcImageChange(event) {
            const file = event.target?.files?.[0] || null;
            this.scan.file = file;
            this.scan.error = '';
            this.scan.status = '';

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
        },

        normalizeDate(raw) {
            const value = String(raw || '').trim();
            if (!value) {
                return '';
            }

            const slash = value.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (slash) {
                const month = slash[1].padStart(2, '0');
                const day = slash[2].padStart(2, '0');
                return `${slash[3]}-${month}-${day}`;
            }

            const dash = value.match(/^(\d{4})-(\d{1,2})-(\d{1,2})$/);
            if (dash) {
                return `${dash[1]}-${dash[2].padStart(2, '0')}-${dash[3].padStart(2, '0')}`;
            }

            return '';
        },

        async scanSignupQcId() {
            this.scan.error = '';
            this.scan.status = '';

            if (!this.scan.file) {
                this.scan.error = 'Upload your QC ID image first.';
                return;
            }

            if (!window.Tesseract) {
                this.scan.error = 'OCR scanner is not available. Please refresh and try again.';
                return;
            }

            this.scan.isProcessing = true;
            this.scan.status = 'Reading QC ID image...';

            try {
                const result = await window.Tesseract.recognize(this.scan.file, 'eng');
                const ocrText = String(result?.data?.text || '').trim();

                if (ocrText.length < 20) {
                    throw new Error('No readable text was found. Please upload a clearer QC ID image.');
                }

                this.signup.ocr_text = ocrText;
                this.scan.status = 'Validating QC ID data...';

                const response = await fetch(@json(route('signup.qcid.verify')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ocr_text: ocrText,
                        user_name: this.signup.name || null,
                    }),
                });

                const payload = await response.json();
                const verification = payload?.verification || {};

                if (!payload?.success) {
                    this.scan.error = payload?.message || 'QC ID verification failed. Please check the image and try again.';
                } else {
                    this.scan.status = 'QC ID verified and fields auto-filled. Please review before creating account.';
                }

                if (verification.cardholder_name) {
                    this.signup.name = verification.cardholder_name;
                }
                if (verification.id_number) {
                    this.signup.qcid_number = verification.id_number;
                }
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
                    this.signup.address = verification.address;
                }
            } catch (error) {
                this.scan.error = error?.message || 'Unable to scan the QC ID image right now.';
            } finally {
                this.scan.isProcessing = false;
            }
        },
    };
}
</script>
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
        animation: floatCard 8s ease-in-out infinite;
    }

    .login-brand-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 1rem;
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
