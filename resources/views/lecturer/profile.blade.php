@extends('layouts.app')
@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('content')
<div class="py-4 max-w-2xl mx-auto">
    <div class="bg-surface  border border-edge shadow-sm p-6">
        <div class="flex items-center gap-4 mb-6 pb-6 border-b">
            <div class="w-16 h-16  bg-blue flex items-center justify-center text-base text-xl font-bold rounded-full">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </div>
            <div>
                <h2 class="text-lg font-semibold text-fg">{{ $user->name }}</h2>
                <p class="text-sm text-fg-2">{{ $user->email }}</p>
                <span class="text-xs seal seal-active px-2 py-0.5  mt-1 inline-block">Lecturer</span>
            </div>
        </div>
        <form method="POST" action="{{ route('lecturer.profile.update') }}" class="space-y-4">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-fg-2 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ $user->name }}" required class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-blue">
                </div>
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Staff ID</label>
                    <input type="text" value="{{ $user->staff_id }}" disabled class="w-full border border-edge bg-base  px-3 py-2 text-sm text-fg-2">
                </div>
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ $user->phone }}" class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-blue">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-fg-2 mb-1">Faculty</label>
                    <input type="text" name="faculty" value="{{ $user->faculty }}" class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:border-blue">
                </div>
            </div>
            <button type="submit" class="btn-primary">Update Profile</button>
        </form>
    </div>

    <div class="bg-surface border border-edge shadow-sm p-6 mt-5">
        <h3 class="text-sm font-semibold text-fg mb-1">Your class code</h3>
        <p class="text-xs text-fg-2 mb-4">Share this code with your students — they enter it when they register (or from their own profile) to be enrolled with you. Only students with this code see the cases you publish.</p>
        <div class="flex items-center gap-3">
            <span class="font-mono text-2xl font-bold tracking-[0.3em] text-blue bg-blue-soft px-4 py-2 rounded-lg">{{ $user->class_code }}</span>
            <form method="POST" action="{{ route('lecturer.profile.regenerate-class-code') }}"
                onsubmit="return confirm('Generate a new code? Students still using the old code won\'t be able to join with it anymore.')">
                @csrf
                <button type="submit" class="btn-ghost text-xs">Regenerate</button>
            </form>
        </div>
    </div>
</div>
@endsection
