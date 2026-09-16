@extends('layouts.app')
@section('title', 'Forgot password — AI-ForensicEdu')

@section('content')
<div class="min-h-screen bg-base flex items-center justify-center p-8">
    <div class="w-full max-w-[380px]">

        <div class="mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="AI-ForensicEDU" class="h-9 w-auto object-contain object-left">
        </div>

        <p class="eyebrow mb-2">Access</p>
        <h1 class="font-display text-[26px] font-semibold text-fg mb-3">Reset your password</h1>
        <p class="text-[13px] text-fg-2 mb-7 leading-relaxed">
            Enter the email address on your account and we'll send you a link to set a new password.
        </p>

        @if(session('success'))
            <div class="bg-blue-soft border-l-2 border-blue px-4 py-3 mb-5">
                <p class="text-[13px] text-blue">{{ session('success') }}</p>
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-soft border-l-2 border-red px-4 py-3 mb-5">
                <p class="text-[13px] text-red">{{ $errors->first() }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
            @csrf
            <div>
                <label class="eyebrow block mb-1.5" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="fld">
            </div>
            <button type="submit" class="btn-primary w-full mt-2">
                Send reset link
            </button>
        </form>

        <p class="text-[13px] text-fg-2 mt-5">
            <a href="{{ route('login') }}" class="text-blue font-medium hover:underline">← Back to sign in</a>
        </p>
    </div>
</div>
@endsection
