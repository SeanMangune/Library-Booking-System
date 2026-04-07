{{-- Email OTP Verification Modal for Registration --}}
{{-- Shown on top of the signup modal when user clicks "Create Account" --}}
<div x-show="otpModalOpen" x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
     @keydown.escape.window="if(otpModalOpen) otpModalOpen = false">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-md transition-opacity"
         x-show="otpModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="otpModalOpen = false"></div>

    <!-- Modal Box -->
    <div class="relative z-10 w-full max-w-[440px] bg-white rounded-[2rem] overflow-hidden shadow-[0_40px_120px_-20px_rgba(30,41,59,0.85)] border border-white/50"
         @click.stop
         x-show="otpModalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-90 translate-y-6"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-90 translate-y-6">

        <!-- Abstract background blobs -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden rounded-[2rem]">
            <div class="absolute -top-16 -left-16 w-48 h-48 bg-teal-400/15 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-12 -right-12 w-40 h-40 bg-emerald-400/15 rounded-full blur-3xl"></div>
        </div>

        <div class="relative">
            <!-- Header -->
            <div class="text-center pt-8 pb-5 px-8">
                <div class="w-16 h-16 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-teal-500/25 transform rotate-3">
                    <i class="fa-solid fa-envelope-open-text text-white text-2xl"></i>
                </div>
                <h2 class="text-2xl font-black tracking-tight text-gray-900">Verify Your Email</h2>
                <p class="text-gray-500 text-sm mt-2">
                    We've sent a 6-digit code to
                    <span class="font-bold text-gray-800 break-all" x-text="otpEmail"></span>
                </p>
            </div>

            <!-- Content -->
            <div class="px-8 pb-8">
                <!-- Success message -->
                <div x-show="otpStatus" x-cloak x-transition
                     class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-100 flex items-start gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                    <p class="text-sm font-medium text-emerald-800" x-text="otpStatus"></p>
                </div>

                <!-- Error message -->
                <div x-show="otpError" x-cloak x-transition
                     class="mb-5 p-3.5 rounded-xl bg-red-50 border border-red-100 flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 mt-0.5 shrink-0"></i>
                    <p class="text-sm font-medium text-red-800" x-text="otpError"></p>
                </div>

                <!-- OTP Input -->
                <div class="space-y-1.5 mb-5">
                    <label class="text-sm font-semibold text-gray-700 ml-1">6-Digit Code</label>
                    <input type="text"
                           x-model="otpCode"
                           maxlength="6"
                           pattern="\d{6}"
                           inputmode="numeric"
                           autocomplete="one-time-code"
                           autofocus
                           @input="otpCode = otpCode.replace(/[^0-9]/g, '').substring(0, 6)"
                           class="w-full text-center tracking-[0.8em] text-2xl font-bold bg-white border-2 border-teal-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 p-4 transition-all outline-none placeholder:tracking-[0.5em] placeholder:text-gray-300"
                           placeholder="······"
                           :disabled="otpVerifying">
                </div>

                <!-- Verify Button -->
                <button type="button"
                        @click="verifyRegOtp()"
                        :disabled="otpCode.length !== 6 || otpVerifying"
                        class="w-full text-white bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 font-bold rounded-xl text-sm px-5 py-4 text-center shadow-lg shadow-teal-500/25 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    <template x-if="otpVerifying">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Verifying...
                        </span>
                    </template>
                    <template x-if="!otpVerifying">
                        <span class="flex items-center gap-2">
                            Verify & Create Account <i class="fa-solid fa-check-circle"></i>
                        </span>
                    </template>
                </button>

                <!-- Footer links -->
                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        <button type="button"
                                @click="resendRegOtp()"
                                :disabled="otpResending || otpResendCooldown > 0"
                                class="font-bold text-teal-600 hover:text-teal-500 transition-colors bg-transparent border-none p-0 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="otpResendCooldown <= 0" x-text="otpResending ? 'Sending...' : 'Resend Code'"></span>
                            <span x-show="otpResendCooldown > 0" x-text="'Resend in ' + otpResendCooldown + 's'"></span>
                        </button>
                        &bull;
                        <button type="button"
                                @click="otpModalOpen = false; otpError = ''; otpCode = '';"
                                class="font-bold text-gray-600 hover:text-gray-900 transition-colors bg-transparent border-none p-0 cursor-pointer">
                            Back to Sign Up
                        </button>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
