@extends('layouts.guest')

@section('title', 'Welcome | SmartSpace')

@section('content')
<div class="min-h-screen bg-[#e8edf3] text-slate-900">
    <header class="sticky top-0 z-50 px-4 pt-5">
        <div class="mx-auto flex w-full max-w-md items-center justify-between rounded-3xl border border-slate-200 bg-white px-4 py-3 shadow-sm ring-1 ring-white/70">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg">
                    <i class="fa-solid fa-book-open text-lg"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">SmartSpace</p>
                    <p class="text-xs text-blue-600">Library Booking on Mobile</p>
                </div>
            </div>
            <a href="{{ route('login') }}" class="rounded-xl bg-blue-600 px-6 py-2.5 text-white shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-lg">
                Login
            </a>
        </div>
    </header>

    <div class="mx-auto min-h-screen w-full max-w-md px-4 pb-8 pt-6">

        <main class="pb-2 pt-5">
            <h1 class="text-center font-extrabold tracking-[-0.03em] text-[#16223a]" style="font-size: clamp(4.5rem, 16vw, 6.5rem); line-height: 0.84;">
                Reserve
                <span class="block">Smarter</span>
            </h1>
            <p class="mx-auto mt-5 max-w-[300px] text-center text-[14px] leading-7 text-slate-600">
                Book rooms faster, manage schedules, and stay updated from your phone with a clean mobile-first experience.
            </p>

            <section
                class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
                x-data="{
                    current: 0,
                    slides: [
                        {
                            src: '{{ asset("images/landing/collab.jpg") }}',
                            alt: 'Library collaboration area'
                        },
                        {
                            src: '{{ asset("images/landing/collabb.jpg") }}',
                            alt: 'Students studying in library'
                        },
                        {
                            src: '{{ asset("images/landing/collabbb.jpg") }}',
                            alt: 'Library reading space'
                        },
                        {
                            src: '{{ asset("images/landing/colabbbb.jpg") }}',
                            alt: 'Library room setup'
                        },
                        {
                            src: '{{ asset("images/landing/colabbbbb.jpg") }}',
                            alt: 'Library collaboration workspace'
                        }
                    ]
                }"
                x-init="setInterval(() => { current = (current + 1) % slides.length; }, 4200)"
            >
                <img
                    :src="slides[current].src"
                    :alt="slides[current].alt"
                    class="h-80 w-full object-cover"
                    loading="lazy"
                >
                <div class="flex items-center justify-center gap-2 border-t border-slate-200 bg-slate-50 px-3 py-2.5">
                    <template x-for="(slide, index) in slides" :key="'dot-' + index">
                        <button
                            type="button"
                            @click="current = index"
                            class="h-2.5 rounded-full transition-all hover:opacity-80"
                            :class="current === index ? 'w-6 bg-blue-600' : 'w-2.5 bg-slate-300'"
                            :aria-label="'Go to image ' + (index + 1)"
                        ></button>
                    </template>
                </div>
            </section>

            <section class="mt-6 space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-xl">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                        <i class="fa-solid fa-bolt text-xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">Quick Booking</p>
                    <p class="mt-2 text-[15px] leading-7 text-slate-600">Reserve available rooms in a few taps</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-emerald-200 hover:shadow-xl">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
                        <i class="fa-solid fa-lock text-xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">Secure Access</p>
                    <p class="mt-2 text-[15px] leading-7 text-slate-600">Sign in safely with your existing SmartSpace account</p>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-5 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-violet-200 hover:shadow-xl">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-violet-100 text-violet-600">
                        <i class="fa-solid fa-mobile-screen-button text-xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-900">Install Ready</p>
                    <p class="mt-2 text-[15px] leading-7 text-slate-600">Add to home screen for app-like access</p>
                </div>
            </section>

            <div class="mt-4">
                <a href="{{ route('login') }}" class="inline-flex w-full items-center justify-center rounded-2xl px-4 py-2.5 text-[17px] font-bold shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:bg-blue-800" style="background-color: #1d4ed8; color: #ffffff; border: 1px solid #1e3a8a;">
                    Login
                </a>
            </div>

        </main>
    </div>

    <footer class="mt-6 pb-6">
        <p class="mx-auto w-full max-w-md px-4 text-center text-[11px] text-slate-500">
            Exclusively for Quezon City University Library
        </p>
    </footer>
</div>
@endsection
