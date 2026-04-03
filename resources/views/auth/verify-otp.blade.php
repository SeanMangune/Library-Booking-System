@extends('layouts.guest')

@section('title', 'Verify OTP | SmartSpace')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-slate-50" x-data="otpForm()">
    <!-- Abstract Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-teal-400/20 rounded-full blur-3xl mix-blend-multiply animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-[50%] h-[50%] bg-emerald-400/20 rounded-full blur-3xl mix-blend-multiply animate-blob animation-delay-2000"></div>
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdHRlcm4gaWQ9InNtYWxsR3JpZCIgd2lkdGg9IjEwIiBoZWlnaHQ9IjEwIiBwYXR0ZXJuVW5pdHM9InVzZXJTcGFjZU9uVXNlIj48cGF0aCBkPSJNMTAgMEwwIDBMMCAxMCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDAsIDAsIDAsIDAuMDUpIiBzdHJva2Utd2lkdGg9IjAuNSIvPjwvcGF0dGVybj48cmVjdCB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIGZpbGw9InVybCgjc21hbGxHcmlkKSIvPjxwYXRoIGQ9Ik00MCAwTDAgMEwwIDQwIiBmaWxsPSJub25lIiBzdHJva2U9InJnYmEoMCwgMCwgMCwgMC4wNSkiIHN0cm9rZS13aWR0aD0iMSIvPjwvcGF0dGVybj48L2RlZnM+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0idXJsKCNncmlkKSIvPjwvc3ZnPg==')] opacity-50"></div>
    </div>

    <!-- Container -->
    <div class="w-full max-w-[440px] relative z-10 animate-fade-in-up">
        
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg transform rotate-3 hover:rotate-6 transition-transform">
                    <i class="fa-solid fa-book-open-reader text-white text-2xl"></i>
                </div>
                <span class="text-3xl font-black bg-clip-text text-transparent bg-gradient-to-r from-indigo-900 to-purple-900 tracking-tight">SmartSpace</span>
            </div>
        </div>

        <div class="bg-white/80 backdrop-blur-xl rounded-[2rem] shadow-[0_8px_40px_-12px_rgba(0,0,0,0.1)] border border-white/50 p-8 sm:p-10 relative overflow-hidden">
            
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4 text-teal-600 text-2xl">
                    <i class="fa-solid fa-envelope-open-text"></i>
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-2">Check Your Email</h1>
                <p class="text-gray-500 text-sm">We've sent a 6-digit verification code to <span class="font-semibold text-gray-800">{{ $email }}</span>. Enter it below along with your new password.</p>
            </div>

            @if(session('status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-100 flex items-start gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5"></i>
                    <p class="text-sm font-medium text-emerald-800">{{ session('status') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-100 flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-red-600 mt-0.5"></i>
                    <div class="text-sm font-medium text-red-800">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form action="{{ route('password.verify.post') }}" method="POST" class="space-y-6">
                @csrf
                <input type="hidden" name="email" value="{{ $email }}">

                <!-- OTP Input field -->
                <div class="space-y-1.5 focus-within-group relative">
                    <label for="otp" class="text-sm font-semibold text-gray-700 ml-1">6-Digit Code</label>
                    <input type="text" id="otp" name="otp" required autofocus maxlength="6" pattern="\d{6}"
                        class="w-full text-center tracking-[1em] text-2xl font-bold bg-white/50 border border-teal-200 text-gray-900 rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 block p-4 transition-all outline-none uppercase" 
                        placeholder="&middot;&middot;&middot;&middot;&middot;&middot;">
                </div>

                <div class="space-y-4 pt-2 border-t border-gray-100">
                    <h3 class="text-sm font-bold text-gray-800">Create New Password</h3>
                    <!-- New Password Input -->
                    <div class="space-y-1.5 focus-within-group relative">
                        <label for="password" class="text-xs font-semibold text-gray-700 ml-1">New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-teal-500 transition-colors">
                                <i class="fa-solid fa-lock"></i>
                            </div>
                            <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                class="w-full bg-white/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 block pl-11 p-3.5 transition-all outline-none" 
                                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fa-solid" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                        <p class="text-[10px] text-gray-500 ml-1 mt-1">Must be 8-16 characters with at least one uppercase letter and one number.</p>
                    </div>

                    <!-- Confirm Password Input -->
                    <div class="space-y-1.5 focus-within-group relative">
                        <label for="password_confirmation" class="text-xs font-semibold text-gray-700 ml-1">Confirm New Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-teal-500 transition-colors">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </div>
                            <input :type="showConfirmPassword ? 'text' : 'password'" id="password_confirmation" name="password_confirmation" required
                                class="w-full bg-white/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 block pl-11 p-3.5 transition-all outline-none" 
                                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                            <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fa-solid" :class="showConfirmPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full text-white bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 font-semibold rounded-xl text-sm px-5 py-4 text-center shadow-lg shadow-teal-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    Verify & Reset Password <i class="fa-solid fa-check-circle"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    <form action="{{ route('password.email') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="email" value="{{ $email }}">
                        <button type="submit" class="font-bold text-teal-600 hover:text-teal-500 transition-colors bg-transparent border-none p-0 cursor-pointer">Resend Code</button>
                    </form>
                    &bull;
                    <a href="{{ route('login') }}" class="font-bold text-gray-600 hover:text-gray-900 transition-colors">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('otpForm', () => ({
            showPassword: false,
            showConfirmPassword: false,
        }))
    });
</script>

<style>
    @keyframes blob {
        0% { transform: translate(0px, 0px) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
        100% { transform: translate(0px, 0px) scale(1); }
    }
    .animate-blob {
        animation: blob 7s infinite;
    }
    .animation-delay-2000 {
        animation-delay: 2s;
    }
    .animate-fade-in-up {
        animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
