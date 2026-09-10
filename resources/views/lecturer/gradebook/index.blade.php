@extends('layouts.app')
@section('title', 'Gradebook')
@section('eyebrow', 'Cohort')
@section('page-title', 'Gradebook')

@push('styles')
<style>
    .gb-wrap { overflow-x: auto; }
    .gb { border-collapse: separate; border-spacing: 0; min-width: 100%; }
    .gb th, .gb td { border-right: 1px solid #263a55; border-bottom: 1px solid #263a55; }

    /* Frozen learner column */
    .gb .frz {
        position: sticky; left: 0; z-index: 3;
        background: #111d2e;
        box-shadow: 1px 0 0 #263a55;
        min-width: 230px; max-width: 230px;
    }
    .gb thead .frz { z-index: 5; background: #172a43; }
    .gb tbody tr:hover .frz { background: #172a43; }

    .gb thead th { position: sticky; top: 0; z-index: 2; background: #172a43; }
    .gb-group { background: #05080C !important; color: #fff; z-index: 4 !important; }

    .cell-graded    { background: rgba(0,194,255,0.10); }
    .cell-submitted { background: #111d2e; }
    .cell-progress  { background: #15243a; }
    .cell-none      { background: #111d2e; }
    .cell-low       { background: rgba(239,68,68,0.12); }

    .gb td.num { min-width: 118px; }
</style>
@endpush

@section('content')
<div class="pt-6 space-y-5">

    {{-- Toolbar --}}
    <form method="GET" class="bg-surface border border-edge px-5 py-3.5 flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[220px]">
            <label class="eyebrow block mb-1.5">Search learner</label>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Name, email, or student ID"
                class="fld">
        </div>

        <div>
            <label class="eyebrow block mb-1.5">Per page</label>
            <select name="per_page" onchange="this.form.submit()"
                class="bg-surface border border-edge px-3 py-2 text-[13px] focus:outline-none focus:border-blue">
                @foreach([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn-primary text-[12.5px]">
            Apply
        </button>

        @if(request('search'))
        <a href="{{ route('lecturer.gradebook') }}"
            class="font-mono text-[10.5px] uppercase tracking-[0.1em] text-fg-2 border border-edge px-3 py-2 hover:bg-surface hover:text-fg hover:border-blue transition">
            Reset
        </a>
        @endif

        <a href="{{ route('lecturer.gradebook.export') }}"
            class="ml-auto font-mono text-[10.5px] uppercase tracking-[0.1em] text-blue border border-blue px-3 py-2 hover:bg-blue hover:text-base transition">
            Export CSV
        </a>
    </form>

    @if($cases->isEmpty())
        <div class="bg-surface border border-edge px-6 py-16 text-center">
            <p class="font-display text-[16px] font-semibold text-fg">No cases to grade</p>
            <p class="text-[13px] text-fg-2 mt-1.5">Create and publish a case before the gradebook has anything to show.</p>
        </div>
    @elseif($rows->isEmpty())
        <div class="bg-surface border border-edge px-6 py-16 text-center">
            <p class="font-display text-[16px] font-semibold text-fg">No learners found</p>
            <p class="text-[13px] text-fg-2 mt-1.5">
                {{ request('search') ? 'No learner matches that search.' : 'No students have enrolled in your cases yet.' }}
            </p>
        </div>
    @else

    {{-- Matrix --}}
    <div class="bg-surface border border-edge">
        <div class="gb-wrap" style="max-height: 62vh; overflow-y: auto;">
            <table class="gb text-[13px]">
                <thead>
                    {{-- Group band --}}
                    <tr>
                        <th class="frz gb-group text-left px-4 py-2">
                            <span class="font-mono text-[9.5px] uppercase tracking-[0.16em]">Learner</span>
                        </th>
                        <th class="gb-group px-3 py-2 text-center" colspan="3">
                            <span class="font-mono text-[9.5px] uppercase tracking-[0.16em]">Overall</span>
                        </th>
                        <th class="gb-group px-3 py-2 text-center" colspan="{{ $cases->count() }}">
                            <span class="font-mono text-[9.5px] uppercase tracking-[0.16em]">Case scores</span>
                        </th>
                    </tr>
                    {{-- Column heads --}}
                    <tr>
                        <th class="frz px-4 py-2.5 text-left" style="top: 33px;">
                            <span class="eyebrow">Name &amp; ID</span>
                        </th>
                        <th class="px-3 py-2.5 text-center" style="top: 33px;"><span class="eyebrow">Complete</span></th>
                        <th class="px-3 py-2.5 text-center" style="top: 33px;"><span class="eyebrow">Class grade</span></th>
                        <th class="px-3 py-2.5 text-center" style="top: 33px;"><span class="eyebrow">Average</span></th>
                        @foreach($cases as $case)
                        <th class="px-3 py-2.5 text-left num" style="top: 33px;">
                            <p class="font-mono text-[9.5px] text-fg-3 tracking-wider">CASE-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}</p>
                            <p class="font-display text-[12px] font-semibold text-fg leading-tight mt-0.5">{{ Str::limit($case->title, 22) }}</p>
                            <p class="font-mono text-[9.5px] text-fg-3 mt-0.5">
                                /{{ $case->total_marks }} · {{ $case->is_published ? 'live' : 'draft' }}
                            </p>
                        </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach($rows as $row)
                    @php $s = $row['student']; @endphp
                    <tr>
                        {{-- Learner --}}
                        <td class="frz px-4 py-3">
                            <div class="flex items-start gap-2.5">
                                <div class="w-7 h-7 border border-edge flex items-center justify-center flex-shrink-0 mt-0.5">
                                    <span class="font-mono text-[9.5px] font-semibold text-blue">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-fg text-[12.5px] leading-tight">{{ $s->name }}</p>
                                    <p class="font-mono text-[10px] text-fg-3 truncate">{{ $s->student_id ?? $s->email }}</p>
                                    <span class="seal {{ $row['complete'] ? 'seal-graded' : 'seal-draft' }} mt-1.5">
                                        {{ $row['complete'] ? 'Complete' : 'In progress' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        {{-- Completion --}}
                        <td class="px-3 py-3 text-center">
                            <p class="font-mono text-[12px] text-fg">{{ $row['submitted'] }}<span class="text-fg-3">/{{ $row['enrolled'] }}</span></p>
                        </td>

                        {{-- Class grade --}}
                        <td class="px-3 py-3 text-center {{ $row['class_grade'] !== null && $row['class_grade'] < 50 ? 'cell-low' : '' }}">
                            @if($row['class_grade'] !== null)
                                <p class="font-display text-[15px] font-semibold {{ $row['class_grade'] < 50 ? 'text-red' : 'text-fg' }}">
                                    {{ number_format($row['class_grade'], 1) }}%
                                </p>
                            @else
                                <span class="text-fg-3">—</span>
                            @endif
                        </td>

                        {{-- Average --}}
                        <td class="px-3 py-3 text-center">
                            @if($row['average'] !== null)
                                <p class="font-mono text-[12.5px] text-fg">{{ $row['average'] }}%</p>
                            @else
                                <span class="text-fg-3">—</span>
                            @endif
                        </td>

                        {{-- Case cells --}}
                        @foreach($cases as $case)
                        @php $c = $row['cells'][$case->id]; @endphp

                        @if($c['state'] === 'graded')
                        <td class="num px-3 py-3 {{ $c['pct'] < 50 ? 'cell-low' : 'cell-graded' }}">
                            <a href="{{ route('lecturer.report.show', [$case, $c['enrollment']]) }}" class="block group">
                                <p class="font-display text-[14px] font-semibold {{ $c['pct'] < 50 ? 'text-red' : 'text-blue' }} group-hover:underline">
                                    {{ $c['marks'] }}<span class="text-[11px] text-fg-3">/{{ $c['total'] }}</span>
                                </p>
                                <p class="font-mono text-[9.5px] text-fg-3 mt-0.5">{{ $c['date']?->format('d M Y') }}</p>
                            </a>
                        </td>

                        @elseif($c['state'] === 'submitted')
                        <td class="num px-3 py-3 cell-submitted">
                            <a href="{{ route('lecturer.report.show', [$case, $c['enrollment']]) }}" class="block group">
                                <span class="seal seal-open group-hover:bg-blue group-hover:text-base transition">Grade</span>
                                <p class="font-mono text-[9.5px] text-fg-3 mt-1">{{ $c['date']?->format('d M Y') }}</p>
                            </a>
                        </td>

                        @elseif($c['state'] === 'in_progress')
                        <td class="num px-3 py-3 cell-progress">
                            <div class="h-[3px] bg-edge w-14 mb-1">
                                <div class="h-[3px] bg-blue" style="width: {{ $c['progress'] }}%"></div>
                            </div>
                            <p class="font-mono text-[9.5px] text-fg-3">{{ $c['progress'] }}% open</p>
                        </td>

                        @else
                        <td class="num px-3 py-3 cell-none text-center">
                            <span class="text-fg-3 font-mono text-[11px]">—</span>
                        </td>
                        @endif
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>

                {{-- Column averages --}}
                <tfoot>
                    <tr class="bg-base">
                        <td class="frz px-4 py-3" style="background:#172a43;">
                            <span class="eyebrow">Case average</span>
                        </td>
                        <td colspan="3"></td>
                        @foreach($cases as $case)
                        <td class="num px-3 py-3">
                            @if($caseStats[$case->id]['avg'] !== null)
                                <p class="font-display text-[13px] font-semibold text-fg">{{ $caseStats[$case->id]['avg'] }}%</p>
                                <p class="font-mono text-[9.5px] text-fg-3">n={{ $caseStats[$case->id]['graded'] }}</p>
                            @else
                                <span class="text-fg-3 font-mono text-[11px]">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-5 py-3.5 border-t border-edge flex flex-wrap items-center justify-between gap-3">
            <p class="font-mono text-[10.5px] text-fg-3">
                Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }} learners
            </p>

            @if($students->hasPages())
            <nav class="flex items-center gap-1.5" aria-label="Pagination">
                @if($students->onFirstPage())
                    <span class="font-mono text-[10.5px] uppercase tracking-[0.1em] border border-edge px-3 py-1.5 text-fg-3 rounded-md">Prev</span>
                @else
                    <a href="{{ $students->previousPageUrl() }}"
                        class="font-mono text-[10.5px] uppercase tracking-[0.1em] border border-edge px-3 py-1.5 text-fg-2 hover:bg-surface hover:text-fg hover:border-blue transition rounded-md">Prev</a>
                @endif

                @foreach(range(1, $students->lastPage()) as $p)
                    @if($p == $students->currentPage())
                        <span class="font-mono text-[10.5px] border border-blue bg-blue text-base px-3 py-1.5 rounded-md">{{ $p }}</span>
                    @elseif(abs($p - $students->currentPage()) <= 2 || $p == 1 || $p == $students->lastPage())
                        <a href="{{ $students->url($p) }}"
                            class="font-mono text-[10.5px] border border-edge px-3 py-1.5 text-fg-2 hover:bg-surface hover:text-fg hover:border-blue transition rounded-md">{{ $p }}</a>
                    @elseif(abs($p - $students->currentPage()) == 3)
                        <span class="font-mono text-[10.5px] px-1.5 py-1.5 text-fg-3">…</span>
                    @endif
                @endforeach

                @if($students->hasMorePages())
                    <a href="{{ $students->nextPageUrl() }}"
                        class="font-mono text-[10.5px] uppercase tracking-[0.1em] border border-edge px-3 py-1.5 text-fg-2 hover:bg-surface hover:text-fg hover:border-blue transition rounded-md">Next</a>
                @else
                    <span class="font-mono text-[10.5px] uppercase tracking-[0.1em] border border-edge px-3 py-1.5 text-fg-3 rounded-md">Next</span>
                @endif
            </nav>
            @endif
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-5 font-mono text-[10px] text-white/60 uppercase tracking-[0.1em]">
        <span class="flex items-center gap-2"><span class="w-3 h-3 border border-white/30" style="background:rgba(0,194,255,0.10)"></span> Graded</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 border border-white/30 bg-surface"></span> Awaiting grade</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 border border-white/30" style="background:#15243a"></span> In progress</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 border border-white/30" style="background:rgba(239,68,68,0.12)"></span> Below 50%</span>
        <span class="ml-auto normal-case tracking-normal text-[11px] text-white/60">Click any score to open that report.</span>
    </div>

    @endif
</div>
@endsection
