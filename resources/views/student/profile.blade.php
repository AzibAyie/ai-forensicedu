@extends('layouts.app')
@section('title', 'Profile')
@section('eyebrow', 'Account')
@section('page-title', 'Profile')

@section('content')
<div class="pt-6 max-w-2xl mx-auto space-y-6">

    <section class="bg-black text-white px-6 py-5 flex items-center gap-4 rounded-2xl">
        <div class="w-11 h-11 border border-blue flex items-center justify-center flex-shrink-0">
            <span class="font-mono text-[14px] font-semibold text-blue">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
        </div>
        <div class="min-w-0">
            <h2 class="font-display text-[16px] font-semibold leading-tight">{{ $user->name }}</h2>
            <p class="font-mono text-[10.5px] text-white/70 mt-0.5">{{ $user->email }}</p>
        </div>
        <span class="seal seal-active ml-auto">Student</span>
    </section>

    <section class="bg-surface border border-edge">
        <div class="px-6 py-4 border-b border-edge">
            <h3 class="font-display text-[14px] font-semibold text-fg">Details</h3>
            <p class="text-[12px] text-fg-2 mt-0.5">Your name and programme appear on every report you file.</p>
        </div>

        <form method="POST" action="{{ route('student.profile.update') }}" class="px-6 py-5 space-y-4">
            @csrf @method('PATCH')

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="eyebrow block mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ $user->name }}" required
                        class="fld">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Student ID</label>
                    <input type="text" value="{{ $user->student_id ?? '—' }}" disabled
                        class="w-full bg-base border border-edge px-3.5 py-2.5 text-[13.5px] font-mono text-fg-3">
                    <p class="text-[11px] text-fg-3 mt-1">Set at enrolment. Contact your lecturer to change it.</p>
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Phone</label>
                    <input type="text" name="phone" value="{{ $user->phone }}"
                        class="fld">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Faculty</label>
                    <input type="text" name="faculty" value="{{ $user->faculty }}"
                        class="fld">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Programme</label>
                    <input type="text" name="program" value="{{ $user->program }}"
                        class="fld">
                </div>
            </div>

            <button type="submit" class="btn-primary text-[13px]">
                Save changes
            </button>
        </form>
    </section>

    <section class="bg-surface border border-edge">
        <div class="px-6 py-4 border-b border-edge">
            <h3 class="font-display text-[14px] font-semibold text-fg">Your class</h3>
            <p class="text-[12px] text-fg-2 mt-0.5">You can only see and attempt cases published by your assigned lecturer.</p>
        </div>
        <div class="px-6 py-5">
            @if($user->lecturer)
                <p class="text-[13px] text-fg mb-4">
                    Currently enrolled with <strong>{{ $user->lecturer->name }}</strong>.
                </p>
            @else
                <p class="text-[13px] text-fg-2 mb-4">
                    You're not enrolled with a lecturer yet — enter a class code to see their cases.
                </p>
            @endif
            <form method="POST" action="{{ route('student.join-class') }}" class="flex gap-2 max-w-sm">
                @csrf
                <input type="text" name="class_code" required maxlength="8" placeholder="e.g. 7K2PXQ"
                    class="fld font-mono uppercase" style="letter-spacing:0.15em">
                <button type="submit" class="btn-primary text-[13px] px-4 flex-shrink-0">
                    {{ $user->lecturer ? 'Switch' : 'Join' }}
                </button>
            </form>
            @if($user->lecturer)
                <p class="text-[11px] text-fg-3 mt-2">Entering a new code switches you to that lecturer's class instead.</p>
            @endif
        </div>
    </section>

    <p class="text-[12.5px] text-fg-2">
        Looking for your scores and case history?
        <a href="{{ route('student.record') }}" class="text-blue font-medium hover:underline">Open your record →</a>
    </p>
</div>
@endsection
