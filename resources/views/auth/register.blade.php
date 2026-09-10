@extends('layouts.app')
@section('title', 'Register — AI-ForensicEdu')

@section('content')
<div class="min-h-screen bg-base flex items-center justify-center p-8">
    <div class="w-full max-w-[520px]">

        <div class="flex items-center gap-3 mb-8">
            <div class="w-9 h-9 bg-blue flex items-center justify-center rounded-lg">
                <span class="font-display font-bold text-[14px] text-white">FE</span>
            </div>
            <p class="font-display text-[15px] font-semibold text-fg">AI-ForensicEdu</p>
        </div>

        <p class="eyebrow mb-2">Enrolment</p>
        <h1 class="font-display text-[26px] font-semibold text-fg mb-7">Create your account</h1>

        @if($errors->any())
            <div class="bg-red-soft border-l-2 border-red px-4 py-3 mb-5">
                <ul class="text-[13px] text-red space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" x-data="{ role: '{{ old('role', 'student') }}' }" class="space-y-5">
            @csrf

            {{-- Role --}}
            <div>
                <p class="eyebrow mb-2">I am a</p>
                <div class="grid grid-cols-2 gap-px bg-edge border border-edge rounded-xl overflow-hidden">
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="student" x-model="role" class="sr-only">
                        <div :class="role === 'student' ? 'bg-blue text-white' : 'bg-surface text-fg-2'"
                            class="px-4 py-3.5 transition">
                            <p class="font-display text-[14px] font-semibold">Student</p>
                            <p class="text-[11.5px] opacity-70 mt-0.5">Investigate cases, file reports</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="role" value="lecturer" x-model="role" class="sr-only">
                        <div :class="role === 'lecturer' ? 'bg-blue text-white' : 'bg-surface text-fg-2'"
                            class="px-4 py-3.5 transition">
                            <p class="font-display text-[14px] font-semibold">Lecturer</p>
                            <p class="text-[11.5px] opacity-70 mt-0.5">Author cases, grade reports</p>
                        </div>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="eyebrow block mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="fld">
                </div>
                <div class="col-span-2">
                    <label class="eyebrow block mb-1.5">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required
                        class="fld">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="fld">
                    <p class="text-[11px] text-fg-3 mt-1">At least 8 characters</p>
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Confirm password</label>
                    <input type="password" name="password_confirmation" required
                        class="fld">
                </div>

                <div x-show="role === 'student'">
                    <label class="eyebrow block mb-1.5">Student ID</label>
                    <input type="text" name="student_id" value="{{ old('student_id') }}" placeholder="A21EC0001"
                        class="w-full bg-surface border border-edge px-3.5 py-2.5 text-[13.5px] font-mono focus:outline-none focus:border-blue transition">
                </div>
                <div x-show="role === 'lecturer'" x-cloak>
                    <label class="eyebrow block mb-1.5">Staff ID</label>
                    <input type="text" name="staff_id" value="{{ old('staff_id') }}" placeholder="L001234"
                        class="w-full bg-surface border border-edge px-3.5 py-2.5 text-[13.5px] font-mono focus:outline-none focus:border-blue transition">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Faculty</label>
                    <input type="text" name="faculty" value="{{ old('faculty') }}" placeholder="Faculty of Computing"
                        class="fld">
                </div>
                <div x-show="role === 'student'" class="col-span-2">
                    <label class="eyebrow block mb-1.5">Programme</label>
                    <input type="text" name="program" value="{{ old('program') }}" placeholder="Bachelor of Cybersecurity"
                        class="fld">
                </div>
            </div>

            <button type="submit"
                class="w-full bg-black text-white font-semibold text-[13.5px] py-3 hover:bg-blue transition rounded-lg">
                Create account
            </button>
        </form>

        <p class="text-[13px] text-fg-2 mt-5">
            Already enrolled?
            <a href="{{ route('login') }}" class="text-blue font-medium hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
