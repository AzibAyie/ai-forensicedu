@extends('layouts.app')
@section('title', 'Sign in — AI-ForensicEdu')

@section('content')
<div class="min-h-screen flex">

    {{-- Left: brand panel --}}
    <div class="relative hidden lg:flex lg:w-[46%] bg-black flex-col justify-between p-12 overflow-hidden">
        <img src="{{ asset('images/logo-icon.png') }}" alt="" class="pointer-events-none absolute -right-10 -top-14 w-72 h-72 object-contain opacity-[0.08]">
        <img src="{{ asset('images/logo.png') }}" alt="AI-ForensicEDU" class="relative h-11 w-auto object-contain object-left">

        <div class="relative">
            <p class="eyebrow text-blue mb-4">Digital forensics · case-based learning</p>
            <h2 class="font-display text-[34px] font-semibold text-white leading-[1.15] mb-5">
                Read the logs.<br>Build the timeline.<br>Name the intrusion.
            </h2>
            <p class="text-[14px] text-white/70 leading-relaxed max-w-sm">
                Investigate simulated breaches using real audit trails, database records, and network captures — then file a report your lecturer grades.
            </p>
        </div>

        <div class="relative font-mono text-[10px] text-white/50 uppercase tracking-[0.14em] space-y-1.5">
            <p>Brute force · Data modification · Mass deletion</p>
            <p>Evidence panels · Timeline reconstruction · AI-assisted grading</p>
        </div>
    </div>

    {{-- Right: form --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-base">
        <div class="w-full max-w-[380px]">

            <div class="lg:hidden mb-9">
                <img src="{{ asset('images/logo.png') }}" alt="AI-ForensicEDU" class="h-9 w-auto object-contain object-left">
            </div>

            <p class="eyebrow mb-2">Access</p>
            <h1 class="font-display text-[26px] font-semibold text-fg mb-7">Sign in</h1>

            @if($errors->any())
                <div class="bg-red-soft border-l-2 border-red px-4 py-3 mb-5">
                    <p class="text-[13px] text-red">{{ $errors->first() }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="eyebrow block mb-1.5" for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        class="fld">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5" for="password">Password</label>
                    <input id="password" type="password" name="password" required
                        class="fld">
                </div>
                <label class="flex items-center gap-2 cursor-pointer pt-1">
                    <input type="checkbox" name="remember" class="accent-blue">
                    <span class="text-[12.5px] text-fg-2">Keep me signed in</span>
                </label>
                <button type="submit" class="btn-primary w-full mt-2">
                    Sign in
                </button>
            </form>

            <p class="text-[13px] text-fg-2 mt-5">
                Need an account?
                <a href="{{ route('register') }}" class="text-blue font-medium hover:underline">Register</a>
            </p>

            {{-- Demo credentials --}}
            <div class="mt-9 border-t border-edge pt-5">
                <p class="eyebrow mb-3">Demo accounts</p>
                <div class="space-y-2 font-mono text-[11px]">
                    <div class="flex justify-between gap-3">
                        <span class="text-fg-3">Lecturer</span>
                        <span class="text-fg">lecturer@forensicedu.test</span>
                    </div>
                    <div class="flex justify-between gap-3">
                        <span class="text-fg-3">Student</span>
                        <span class="text-fg">student@forensicedu.test</span>
                    </div>
                    <div class="flex justify-between gap-3 pt-1.5 border-t border-edge">
                        <span class="text-fg-3">Password</span>
                        <span class="text-fg">password</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
