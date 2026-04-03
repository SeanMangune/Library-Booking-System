@extends('layouts.guest')

@section('title', 'Forgot Password | SmartSpace')

@section('content')
<div class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden bg-slate-50">
    <!-- Abstract Background -->
    <div class="absolute inset-0 z-0">
        <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-400/20 rounded-full blur-3xl mix-blend-multiply animate-blob"></div>
        <div class="absolute top-[20%] right-[-10%] w-[50%] h-[50%] bg-purple-400/20 rounded-full blur-3xl mix-blend-multiply animate-blob animation-delay-2000"></div>
        <div class="absolute bottom-[-20%] left-[20%] w-[60%] h-[60%] bg-blue-400/20 rounded-full blur-3xl mix-blend-multiply animate-blob animation-delay-4000"></div>
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
                <h1 class="text-2xl font-bold tracking-tight text-gray-900 mb-2">Forgot Password?</h1>
                <p class="text-gray-500 text-sm">Enter the email address you registered with and we'll send you a 6-digit verification code to reset your password.</p>
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

            <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
                @csrf
                <!-- Email Input -->
                <div class="space-y-1.5 focus-within-group relative">
                    <label for="email" class="text-sm font-semibold text-gray-700 ml-1">Email Address</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            class="w-full bg-white/50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 block pl-11 p-3.5 transition-all outline-none" 
                            placeholder="your.email@example.com">
                    </div>
                </div>

                <button type="submit" class="w-full text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 font-semibold rounded-xl text-sm px-5 py-4 text-center shadow-lg shadow-indigo-500/30 transition-all hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                    Send Verification Code <i class="fa-solid fa-paper-plane text-xs"></i>
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Remember your password? <a href="{{ route('login') }}" class="font-bold text-indigo-600 hover:text-indigo-500 transition-colors">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

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
    .animation-delay-4000 {
        animation-delay: 4s;
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
