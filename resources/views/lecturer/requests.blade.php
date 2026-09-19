@extends('layouts.app')
@section('title', 'Join Requests')
@section('page-title', 'Join Requests')
@section('page-subtitle', 'Students who entered your class code are held here until you approve them.')

@section('content')
<div class="pt-6 space-y-7">

    {{-- Pending --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-heading">Awaiting your decision</h2>
            <p class="font-mono text-[10.5px] text-fg-3">{{ $pending->count() }} pending</p>
        </div>

        @if($pending->isEmpty())
        <div class="bg-surface border border-edge px-6 py-14 text-center">
            <p class="font-display text-[15px] font-semibold text-fg">No pending requests</p>
            <p class="text-[13px] text-fg-2 mt-1.5">Share your class code from your profile — requests to join will show up here.</p>
        </div>
        @else
        <div class="bg-surface border border-edge">
            @foreach($pending as $r)
            <div class="px-5 py-4 border-b border-edge last:border-0 flex items-center gap-3.5">
                <div class="w-9 h-9 border border-edge flex items-center justify-center flex-shrink-0">
                    <span class="font-mono text-[11px] font-semibold text-blue">{{ strtoupper(substr($r->student->name, 0, 2)) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-medium text-fg text-[13.5px] leading-tight">{{ $r->student->name }}</p>
                    <p class="text-[11.5px] text-fg-3 mt-0.5">
                        {{ $r->student->student_id ?? $r->student->email }}
                        @if($r->student->program) · {{ $r->student->program }} @endif
                        · requested {{ $r->created_at->diffForHumans() }}
                    </p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <form method="POST" action="{{ route('lecturer.requests.reject', $r) }}">
                        @csrf
                        <button class="btn-danger px-4 py-2 text-[12.5px]">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('lecturer.requests.accept', $r) }}">
                        @csrf
                        <button class="btn-primary px-4 py-2 text-[12.5px]">Accept</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    {{-- Recent decisions --}}
    @if($decided->count())
    <section x-data="{ open: false }">
        <button @click="open = !open" class="flex items-baseline justify-between mb-4 w-full text-left group">
            <h2 class="font-display text-[15px] font-semibold text-heading group-hover:text-blue transition">
                Recent decisions <span class="font-mono text-[10.5px] text-fg-3 ml-1" x-text="open ? '▾' : '▸'"></span>
            </h2>
            <p class="font-mono text-[10.5px] text-fg-3">{{ $decided->count() }}</p>
        </button>
        <div x-show="open" x-cloak x-transition class="bg-surface border border-edge">
            @foreach($decided as $r)
            <div class="px-5 py-3.5 border-b border-edge last:border-0 flex items-center gap-3">
                <div class="w-8 h-8 border border-edge flex items-center justify-center flex-shrink-0">
                    <span class="font-mono text-[10px] font-semibold text-fg-3">{{ strtoupper(substr($r->student->name, 0, 2)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="font-medium text-fg text-[13px] leading-tight">{{ $r->student->name }}</p>
                    <p class="font-mono text-[10.5px] text-fg-3">{{ $r->decided_at?->diffForHumans() }}</p>
                </div>
                <span class="seal {{ $r->status === 'approved' ? 'seal-graded' : 'seal-alert' }} ml-auto">
                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                    {{ ucfirst($r->status) }}
                </span>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
