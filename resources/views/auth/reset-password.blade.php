@extends('layouts.app')
@section('title', 'Reset password — AI-ForensicEdu')

@section('content')
<div class="min-h-screen bg-base flex items-center justify-center p-8">
    <div class="w-full max-w-[380px]">

        <div class="mb-8">
            <img src="{{ asset('images/logo.png') }}" alt="AI-ForensicEDU" class="h-9 w-auto object-contain object-left">
        </div>

        <p class="eyebrow mb-2">Access</p>
        <h1 class="font-display text-[26px] font-semibold text-fg mb-7">Set a new password</h1>

        @if($errors->any())
            <div class="bg-red-soft border-l-2 border-red px-4 py-3 mb-5">
                <ul class="text-[13px] text-red space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="eyebrow block mb-1.5" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autofocus
                    class="fld">
            </div>
            <div>
                <label class="eyebrow block mb-1.5" for="password">New password</label>
                <input id="password" type="password" name="password" required minlength="8"
                    class="fld">
                <p class="text-[11px] text-fg-3 mt-1">At least 8 characters, with upper &amp; lower case, a number, and a symbol</p>
            </div>
            <div>
                <label class="eyebrow block mb-1.5" for="password_confirmation">Confirm new password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="fld">
            </div>
            <button type="submit" class="btn-primary w-full mt-2">
                Reset password
            </button>
        </form>

        <p class="text-[13px] text-fg-2 mt-5">
            <a href="{{ route('login') }}" class="text-blue font-medium hover:underline">← Back to sign in</a>
        </p>
    </div>
</div>
@endsection
