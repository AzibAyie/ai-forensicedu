@extends('layouts.app')
@section('title', 'Case Registry')
@section('eyebrow', 'Cases')
@section('page-title', 'Case Registry')

@section('content')
<div class="pt-6 space-y-8">

    {{-- STATS --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 border-t border-l border-edge rounded-xl overflow-hidden">
        @foreach([
            ['Cases authored', $stats['total_cases'], $stats['published'].' published'],
            ['Live to students', $stats['published'], 'visible on case board'],
            ['Enrolments', $stats['total_students'], 'across all cases'],
            ['Awaiting grade', $stats['pending_reviews'], $stats['pending_reviews'] > 0 ? 'needs your review' : 'all clear'],
        ] as $s)
        <div class="bg-surface border-r border-b border-edge px-5 py-4">
            <p class="eyebrow mb-2">{{ $s[0] }}</p>
            <p class="font-display text-[26px] font-semibold text-fg leading-none">{{ $s[1] }}</p>
            <p class="text-[11px] text-fg-3 mt-1.5">{{ $s[2] }}</p>
        </div>
        @endforeach
    </section>

    {{-- CASES --}}
    <section>
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-white">Your cases</h2>
            <div class="flex items-center gap-px">
                <a href="{{ route('lecturer.gradebook') }}"
                    class="font-mono text-[10.5px] uppercase tracking-[0.1em] border border-white/30 px-4 py-2.5 text-white/80 hover:bg-surface hover:text-fg hover:border-blue transition rounded-lg">
                    Gradebook
                </a>
                <a href="{{ route('lecturer.case.create') }}" class="btn-primary text-[13px]">
                    New case
                </a>
            </div>
        </div>

        @if($cases->isEmpty())
        <div class="bg-surface border border-edge px-6 py-16 text-center">
            <p class="font-display text-[16px] font-semibold text-fg">No cases yet</p>
            <p class="text-[13px] text-fg-2 mt-1.5 mb-5">Author a case manually, or let the AI generator draft one for you.</p>
            <a href="{{ route('lecturer.case.create') }}" class="btn-primary inline-flex text-[13px]">
                Create your first case
            </a>
        </div>
        @else
        <div class="bg-surface border border-edge">
            @foreach($cases as $case)
            <div class="border-b border-edge last:border-0">
                <div class="px-6 py-5 flex flex-wrap items-start gap-5">

                    {{-- Identity --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                            <span class="font-mono text-[10px] text-fg-3 tracking-wider">
                                CASE-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="seal {{ $case->is_published ? 'seal-graded' : 'seal-draft' }}">
                                {{ $case->is_published ? 'Published' : 'Draft' }}
                            </span>
                            @if($case->is_locked)
                                <span class="seal seal-open">Password</span>
                            @endif
                            @if($case->ai_generated)
                                <span class="seal seal-draft">AI drafted</span>
                            @endif
                            @if($case->question_pdf_path)
                                <span class="seal seal-draft">PDF</span>
                            @endif
                            <span class="font-mono text-[10px] {{ $case->isAvailable() ? 'text-blue' : 'text-fg-3' }}">
                                {{ $case->availabilityLabel() }}
                            </span>
                        </div>
                        <h3 class="font-display text-[15.5px] font-semibold text-fg leading-snug">{{ $case->title }}</h3>
                        <p class="text-[12.5px] text-fg-2 mt-1">{{ $case->incident_label }}</p>

                        <div class="flex items-center gap-5 mt-3 font-mono text-[10.5px] text-fg-3">
                            <span class="diff diff-{{ $case->difficulty }}">{{ $case->difficulty }}</span>
                            <span>{{ $case->enrollments_count }} enrolled</span>
                            <span>{{ $case->submitted_count }} submitted</span>
                            <span>{{ $case->expected_duration }}min</span>
                            <span>{{ $case->total_marks }}pts</span>
                        </div>
                    </div>

                    {{-- Submission ratio --}}
                    <div class="w-32 flex-shrink-0">
                        <p class="eyebrow mb-1.5">Submissions</p>
                        @php $ratio = $case->enrollments_count > 0 ? round($case->submitted_count / $case->enrollments_count * 100) : 0; @endphp
                        <div class="h-[3px] bg-edge mb-1.5">
                            <div class="h-[3px] bg-blue progress-bar" style="width: {{ $ratio }}%"></div>
                        </div>
                        <p class="font-mono text-[10.5px] text-fg-3">{{ $ratio }}%</p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-px flex-shrink-0">
                        <a href="{{ route('lecturer.report.index', $case) }}" class="btn-ai text-[11.5px]">
                            Reports {{ $case->submitted_count > 0 ? '('.$case->submitted_count.')' : '' }}
                        </a>
                        <form method="POST" action="{{ route('lecturer.case.toggle-publish', $case) }}">
                            @csrf @method('PATCH')
                            <button class="font-mono text-[10px] uppercase tracking-[0.1em] border border-edge px-3 py-2 text-fg-2 hover:bg-raised hover:text-fg hover:border-blue transition">
                                {{ $case->is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('lecturer.case.toggle-lock', $case) }}">
                            @csrf @method('PATCH')
                            <button class="font-mono text-[10px] uppercase tracking-[0.1em] border border-edge px-3 py-2 text-fg-2 hover:bg-raised hover:text-fg hover:border-blue transition">
                                {{ $case->is_locked ? 'Unlock' : 'Lock' }}
                            </button>
                        </form>
                        <a href="{{ route('lecturer.case.edit', $case) }}"
                            class="font-mono text-[10px] uppercase tracking-[0.1em] border border-edge px-3 py-2 text-fg-2 hover:bg-raised hover:text-fg hover:border-blue transition">
                            Edit
                        </a>
                        <form method="POST" action="{{ route('lecturer.case.destroy', $case) }}"
                            onsubmit="return confirm('Delete {{ addslashes($case->title) }}? Student work on this case is deleted too. This cannot be undone.')">
                            @csrf @method('DELETE')
                            <button class="font-mono text-[10px] uppercase tracking-[0.1em] border border-edge px-3 py-2 text-red hover:bg-red hover:text-white hover:border-red transition">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

</div>
@endsection
