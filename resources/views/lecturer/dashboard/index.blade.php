@extends('layouts.app')
@section('title', 'Case Registry')
@section('eyebrow', 'Cases')
@section('page-title', 'Case Registry')
@section('page-subtitle', 'Create, publish and manage digital forensics cases for your students.')

@section('content')
<div class="pt-6 space-y-8">

    {{-- STATS --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['icon' => 'file-text', 'accent' => 'blue',    'label' => 'Cases authored',  'value' => $stats['total_cases'],     'sub' => $stats['published'].' published'],
            ['icon' => 'users',     'accent' => 'success', 'label' => 'Live to students', 'value' => $stats['published'],       'sub' => 'visible on case board'],
            ['icon' => 'graduation-cap', 'accent' => 'ai',  'label' => 'Enrolments',       'value' => $stats['total_students'],  'sub' => 'across all cases'],
            ['icon' => 'clock',     'accent' => 'warning', 'label' => 'Awaiting grade',   'value' => $stats['pending_reviews'], 'sub' => $stats['pending_reviews'] > 0 ? 'needs your review' : 'all clear'],
        ] as $s)
        <div class="relative bg-surface border border-edge border-t-2 border-t-{{ $s['accent'] }} overflow-hidden px-5 py-4">
            <i data-lucide="{{ $s['icon'] }}" class="pointer-events-none absolute -right-2 -bottom-2 w-16 h-16 text-{{ $s['accent'] }}/[0.07]" stroke-width="1.5"></i>
            <div class="relative flex items-start gap-3">
                <div class="w-11 h-11 rounded-xl bg-{{ $s['accent'] }}-soft flex items-center justify-center flex-shrink-0">
                    <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5 text-{{ $s['accent'] }}"></i>
                </div>
                <div class="min-w-0">
                    <p class="eyebrow mb-1.5">{{ $s['label'] }}</p>
                    <p class="font-display text-[26px] font-semibold text-heading leading-none">{{ $s['value'] }}</p>
                    <p class="text-[11px] text-fg-3 mt-1.5">{{ $s['sub'] }}</p>
                </div>
            </div>
        </div>
        @endforeach
    </section>

    {{-- CASES --}}
    <section>
        <div class="flex items-end justify-between mb-4 gap-4">
            <div>
                <h2 class="font-display text-[18px] font-semibold text-heading">Your cases</h2>
                <p class="text-[12.5px] text-fg-2 mt-0.5">Manage your cases, view submission progress and access student reports.</p>
            </div>
            <div class="flex items-center gap-2.5 flex-shrink-0">
                <a href="{{ route('lecturer.gradebook') }}" class="btn-ghost inline-flex items-center gap-1.5 !normal-case !tracking-normal !font-sans !text-[13px]">
                    <i data-lucide="book-open" class="w-4 h-4"></i> Gradebook
                </a>
                <a href="{{ route('lecturer.case.create') }}" class="btn-primary text-[13px] !rounded-full">
                    <i data-lucide="plus" class="w-4 h-4"></i> New case
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
        <div class="space-y-4">
            @foreach($cases as $case)
            <div class="bg-surface border border-edge rounded-xl px-6 py-5 flex flex-wrap items-start gap-6 card-interactive">

                {{-- Identity --}}
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2.5 mb-2 flex-wrap">
                        <span class="font-mono text-[10px] text-fg-3 tracking-wider">
                            CASE-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="seal {{ $case->is_published ? 'seal-graded' : 'seal-draft' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $case->is_published ? 'Published' : 'Draft' }}
                        </span>
                        <span class="text-fg-3">|</span>
                        <span class="text-[11.5px] font-medium {{ $case->isAvailable() ? 'text-blue' : 'text-fg-3' }}">
                            {{ $case->availabilityLabel() }}
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
                    </div>
                    <h3 class="font-display text-[15.5px] font-semibold text-fg leading-snug">{{ $case->title }}</h3>
                    <p class="text-[12.5px] text-fg-2 mt-1">{{ $case->incident_label }}</p>

                    <div class="flex items-center flex-wrap gap-x-5 gap-y-1.5 mt-3 text-[11.5px] text-fg-3">
                        <span class="diff diff-{{ $case->difficulty }} inline-flex items-center gap-1.5">
                            <i data-lucide="chart-no-axes-column" class="w-3.5 h-3.5"></i> {{ ucfirst($case->difficulty) }}
                        </span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="users" class="w-3.5 h-3.5"></i> {{ $case->enrollments_count }} enrolled</span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="circle-check-big" class="w-3.5 h-3.5"></i> {{ $case->submitted_count }} submitted</span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i> {{ $case->expected_duration }}min</span>
                        <span class="inline-flex items-center gap-1.5"><i data-lucide="trophy" class="w-3.5 h-3.5"></i> {{ $case->total_marks }}pts</span>
                    </div>
                </div>

                {{-- Submission ratio --}}
                @php $ratio = $case->enrollments_count > 0 ? round($case->submitted_count / $case->enrollments_count * 100) : 0; @endphp
                <div class="w-40 flex-shrink-0">
                    <div class="flex items-baseline justify-between mb-1.5">
                        <p class="eyebrow">Submissions</p>
                        <p class="font-mono text-[11px] text-fg-3">{{ $ratio }}%</p>
                    </div>
                    <div class="h-[5px] bg-edge rounded-full">
                        <div class="h-[5px] bg-blue progress-bar rounded-full" style="width: {{ $ratio }}%"></div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center flex-wrap gap-2 flex-shrink-0">
                    <a href="{{ route('lecturer.report.index', $case) }}" class="btn-ai !text-[12.5px] !py-2 !px-3.5">
                        <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Reports {{ $case->submitted_count > 0 ? '('.$case->submitted_count.')' : '' }}
                    </a>
                    <form method="POST" action="{{ route('lecturer.case.toggle-publish', $case) }}">
                        @csrf @method('PATCH')
                        <button class="btn-ghost !normal-case !tracking-normal !font-sans !text-[12.5px] !py-2 !px-3.5 inline-flex items-center gap-1.5">
                            <i data-lucide="{{ $case->is_published ? 'eye-off' : 'eye' }}" class="w-3.5 h-3.5"></i> {{ $case->is_published ? 'Unpublish' : 'Publish' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('lecturer.case.toggle-lock', $case) }}">
                        @csrf @method('PATCH')
                        <button class="btn-ghost !normal-case !tracking-normal !font-sans !text-[12.5px] !py-2 !px-3.5 inline-flex items-center gap-1.5">
                            <i data-lucide="{{ $case->is_locked ? 'lock-open' : 'lock' }}" class="w-3.5 h-3.5"></i> {{ $case->is_locked ? 'Unlock' : 'Lock' }}
                        </button>
                    </form>
                    <a href="{{ route('lecturer.case.edit', $case) }}"
                        class="btn-ghost !normal-case !tracking-normal !font-sans !text-[12.5px] !py-2 !px-3.5 inline-flex items-center gap-1.5">
                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('lecturer.case.destroy', $case) }}"
                        onsubmit="return confirm('Delete {{ addslashes($case->title) }}? Student work on this case is deleted too. This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button class="btn-danger !normal-case !tracking-normal !font-sans !text-[12.5px] !py-2 !px-3.5 inline-flex items-center gap-1.5">
                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

</div>
@endsection
