@extends('layouts.app')
@section('title', 'My Students')
@section('eyebrow', 'Cohort')
@section('page-title', 'My Students')

@section('content')
<div class="pt-6 space-y-7">

    {{-- Assigned --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-white">Assigned to you</h2>
            <p class="font-mono text-[10.5px] text-white/60">{{ $students->count() }} students</p>
        </div>

        @if($students->isEmpty())
        <div class="bg-surface border border-edge px-6 py-14 text-center">
            <p class="font-display text-[15px] font-semibold text-fg">No students assigned yet</p>
            <p class="text-[13px] text-fg-2 mt-1.5">Assign students from the list below to monitor their progress and view as them.</p>
        </div>
        @else
        <div class="bg-surface border border-edge">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="border-b border-edge">
                        <th class="eyebrow text-left px-5 py-3">Student</th>
                        <th class="eyebrow text-left px-5 py-3">Programme</th>
                        <th class="eyebrow text-center px-5 py-3">Cases</th>
                        <th class="eyebrow text-center px-5 py-3">Filed</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $s)
                    <tr class="border-b border-edge last:border-0 hover:bg-raised">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 border border-edge flex items-center justify-center flex-shrink-0">
                                    <span class="font-mono text-[10px] font-semibold text-blue">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-fg leading-tight">{{ $s->name }}</p>
                                    <p class="font-mono text-[10.5px] text-fg-3 truncate">{{ $s->student_id ?? $s->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-fg-2 text-[12.5px]">{{ $s->program ?? '—' }}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-[12px] text-fg">{{ $s->enrollments->count() }}</td>
                        <td class="px-5 py-3.5 text-center font-mono text-[12px] text-fg">
                            {{ $s->enrollments->whereIn('status', ['submitted','graded'])->count() }}
                        </td>
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-px justify-end">
                                <a href="{{ route('lecturer.progress.show', $s) }}" class="btn-ghost px-3 py-1.5">Progress</a>
                                <form method="POST" action="{{ route('lecturer.impersonate.start', $s) }}"
                                    onsubmit="return confirm('View as {{ addslashes($s->name) }}? This is recorded in the activity log.')">
                                    @csrf
                                    <button class="btn-ghost px-3 py-1.5">View as</button>
                                </form>
                                <form method="POST" action="{{ route('lecturer.students.unassign', $s) }}"
                                    onsubmit="return confirm('Unassign {{ addslashes($s->name) }} from you?')">
                                    @csrf @method('DELETE')
                                    <button class="btn-danger px-3 py-1.5">Unassign</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </section>

    {{-- Unassigned --}}
    @if($unassigned->count())
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-white">Working on your cases, not yet assigned</h2>
            <p class="font-mono text-[10.5px] text-white/60">{{ $unassigned->count() }} students</p>
        </div>
        <div class="bg-surface border border-edge">
            @foreach($unassigned as $s)
            <div class="px-5 py-3.5 border-b border-edge last:border-0 flex items-center gap-3">
                <div class="w-8 h-8 border border-edge flex items-center justify-center flex-shrink-0">
                    <span class="font-mono text-[10px] font-semibold text-fg-3">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="font-medium text-fg text-[13px] leading-tight">{{ $s->name }}</p>
                    <p class="font-mono text-[10.5px] text-fg-3">{{ $s->student_id ?? $s->email }}</p>
                </div>
                <form method="POST" action="{{ route('lecturer.students.assign', $s) }}" class="ml-auto">
                    @csrf @method('PATCH')
                    <button class="btn-primary text-[12px] px-4 py-2">Assign to me</button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- All other students --}}
    @if($otherStudents->count())
    <section x-data="{ open: false }">
        <button @click="open = !open" class="flex items-baseline justify-between mb-4 w-full text-left group">
            <h2 class="font-display text-[15px] font-semibold text-white group-hover:text-blue transition">
                All other students <span class="font-mono text-[10.5px] text-white/60 ml-1" x-text="open ? '▾' : '▸'"></span>
            </h2>
            <p class="font-mono text-[10.5px] text-white/60">{{ $otherStudents->count() }} students</p>
        </button>
        <div x-show="open" x-cloak x-transition class="bg-surface border border-edge">
            @foreach($otherStudents as $s)
            <div class="px-5 py-3.5 border-b border-edge last:border-0 flex items-center gap-3 hover:bg-raised transition">
                <div class="w-8 h-8 border border-edge flex items-center justify-center flex-shrink-0">
                    <span class="font-mono text-[10px] font-semibold text-fg-3">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
                </div>
                <div class="min-w-0">
                    <p class="font-medium text-fg text-[13px] leading-tight">{{ $s->name }}</p>
                    <p class="font-mono text-[10.5px] text-fg-3">{{ $s->student_id ?? $s->email }} @if($s->lecturer)· assigned to {{ $s->lecturer->name }}@endif</p>
                </div>
                <form method="POST" action="{{ route('lecturer.impersonate.start', $s) }}" class="ml-auto"
                    onsubmit="return confirm('View as {{ addslashes($s->name) }}? This is recorded in the activity log.')">
                    @csrf
                    <button class="btn-ghost px-3 py-1.5">View as</button>
                </form>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
