@extends('layouts.app')
@section('title', 'Case Board')
@section('eyebrow', 'Investigation')
@section('page-title', 'Case Board')
@section('page-subtitle', 'Pick up an active investigation or open a new case from the registry.')

@section('content')
<div class="pt-6 space-y-8">

    {{-- ACTIVE CASE — the hero. Only shows when there is one. --}}
    @if($hasActiveCase && $currentEnrollment)
    @php $ac = $currentEnrollment->forensicCase; @endphp
    <section class="relative bg-black text-white rounded-2xl overflow-hidden">
        <i data-lucide="fingerprint" class="pointer-events-none absolute -right-8 -top-10 w-48 h-48 text-blue/[0.08]" stroke-width="1"></i>
        <div class="relative px-7 py-6">
            <div class="flex items-start justify-between gap-6 mb-5">
                <div class="min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="seal seal-active"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> Under Investigation</span>
                        <span class="font-mono text-[10px] text-white/50 tracking-wider">CASE-{{ str_pad($ac->id, 3, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <h2 class="font-display text-[22px] font-semibold text-white leading-tight">{{ $ac->title }}</h2>
                    <p class="text-[13px] text-white/70 mt-1">{{ $ac->incident_label }} · {{ ucfirst($ac->difficulty) }}</p>
                </div>
                <div class="text-right flex-shrink-0">
                    <p class="font-display text-[38px] font-bold text-blue leading-none">{{ $currentEnrollment->progress_percent }}<span class="text-[20px]">%</span></p>
                    <p class="eyebrow !text-white/50 mt-1">Complete</p>
                </div>
            </div>

            <div class="h-[3px] bg-white/10 mb-5">
                <div class="h-[3px] bg-blue progress-bar" style="width: {{ $currentEnrollment->progress_percent }}%"></div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('student.case.show', $ac) }}"
                    class="inline-flex items-center gap-1.5 bg-blue text-base font-semibold text-[13px] px-5 py-2.5 hover:bg-white hover:text-black transition rounded-lg">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> Resume investigation
                </a>
                <a href="{{ route('student.report.show', $ac) }}"
                    class="inline-flex items-center gap-1.5 border border-white/25 text-white text-[13px] px-5 py-2.5 hover:bg-white/10 transition rounded-lg">
                    <i data-lucide="square-pen" class="w-3.5 h-3.5"></i> Write report
                </a>
                <p class="font-mono text-[10.5px] text-white/50 ml-auto">
                    Opened {{ $currentEnrollment->started_at?->diffForHumans() }}
                </p>
            </div>
        </div>
    </section>
    @endif

    {{-- QUICK STATS --}}
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach([
            ['icon' => 'file-text',    'accent' => 'blue',    'label' => 'Cases opened',   'value' => $stats['total_cases'], 'sub' => null],
            ['icon' => 'file-check',   'accent' => 'success', 'label' => 'Reports filed',  'value' => $stats['completed'],   'sub' => null],
            ['icon' => 'percent',      'accent' => 'ai',      'label' => 'Average score',  'value' => $stats['avg_score'] > 0 ? number_format($stats['avg_score'], 0).'%' : '—', 'sub' => null],
            ['icon' => 'flame',        'accent' => 'warning', 'label' => 'Current streak', 'value' => $stats['streak'].'',   'sub' => 'consecutive passing grades'],
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
                    @if($s['sub'])<p class="text-[11px] text-fg-3 mt-1.5">{{ $s['sub'] }}</p>@endif
                </div>
            </div>
        </div>
        @endforeach
    </section>

    {{-- AVAILABLE CASES --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-white">
                {{ $hasActiveCase ? 'Queued cases' : 'Available cases' }}
            </h2>
            <p class="font-mono text-[10.5px] text-white/60">{{ $availableCases->count() }} in registry</p>
        </div>

        @if($hasActiveCase)
        <div class="bg-blue-soft border-l-2 border-blue px-4 py-3 mb-4 flex items-start gap-2.5">
            <span class="seal seal-open mt-0.5">Hold</span>
            <p class="text-[13px] text-blue leading-relaxed">
                One case at a time. File your report on <strong>{{ $currentEnrollment->forensicCase->title }}</strong> to open the next.
            </p>
        </div>
        @endif

        @if($availableCases->isEmpty())
            <div class="bg-surface border border-edge px-6 py-14 text-center">
                <p class="font-display text-[15px] font-semibold text-fg">Registry is empty</p>
                <p class="text-[13px] text-fg-2 mt-1.5">No cases have been published yet. Your lecturer assigns them.</p>
            </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($availableCases as $case)
            <article class="bg-surface border border-edge rounded-xl flex flex-col {{ $hasActiveCase ? 'opacity-55' : 'card-interactive' }}" x-data="{ pwd: false }">

                {{-- Folder tab header --}}
                <div class="px-5 pt-4 pb-3 border-b border-edge">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="font-mono text-[10px] text-fg-3 tracking-wider">
                            CASE-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="seal {{ $case->is_locked ? 'seal-draft' : 'seal-open' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                            {{ $case->is_locked ? 'Sealed' : 'Open' }}
                        </span>
                    </div>
                    <h3 class="font-display text-[15px] font-semibold text-fg leading-snug">{{ $case->title }}</h3>
                    <p class="text-[12px] text-fg-2 mt-1">{{ $case->incident_label }}</p>
                </div>

                {{-- Body --}}
                <div class="px-5 py-4 flex-1">
                    <p class="text-[13px] text-fg-2 leading-relaxed line-clamp-3">{{ $case->description }}</p>
                </div>

                {{-- Metadata strip --}}
                <div class="px-5 py-3 border-t border-edge flex items-center gap-4 text-[11px] text-fg-3">
                    <span class="diff diff-{{ $case->difficulty }} inline-flex items-center gap-1.5">
                        <i data-lucide="chart-no-axes-column" class="w-3.5 h-3.5"></i> {{ ucfirst($case->difficulty) }}
                    </span>
                    <span class="ml-auto inline-flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i> {{ $case->expected_duration }}min</span>
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="circle-help" class="w-3.5 h-3.5"></i> {{ $case->questions->count() }}Q</span>
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="trophy" class="w-3.5 h-3.5"></i> {{ $case->total_marks }}pts</span>
                </div>

                {{-- Action --}}
                <div class="px-5 pb-5 pt-1">
                    @if($hasActiveCase)
                        <p class="font-mono text-[10.5px] text-fg-3 text-center py-2.5 border border-edge">Locked — case in progress</p>
                    @elseif($case->is_locked)
                        <div x-show="!pwd">
                            <button @click="pwd = true" class="btn-primary w-full text-[13px]">
                                <i data-lucide="key-round" class="w-4 h-4"></i> Enter case password
                            </button>
                        </div>
                        <form x-show="pwd" x-cloak x-transition method="POST" action="{{ route('student.case.unlock', $case) }}" class="flex">
                            @csrf
                            <input type="password" name="password" required autofocus placeholder="Password"
                                class="flex-1 min-w-0 border border-edge border-r-0 bg-input text-fg px-3 py-2.5 text-[13px] font-mono focus:outline-none focus:border-blue !rounded-r-none">
                            <button type="submit" class="bg-blue text-base font-semibold text-[13px] px-4 hover:bg-blue-deep transition rounded-lg !rounded-l-none">
                                Unlock
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('student.case.unlock', $case) }}">
                            @csrf
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-1.5 bg-blue text-base text-[13px] font-semibold py-2.5 hover:bg-surface hover:text-fg transition rounded-lg">
                                <i data-lucide="folder-open" class="w-4 h-4"></i> Open case file
                            </button>
                        </form>
                    @endif
                </div>
            </article>
            @endforeach
        </div>
        @endif
    </section>

    {{-- CLOSED CASES --}}
    @if($completedEnrollments->count())
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h2 class="font-display text-[15px] font-semibold text-white">Closed cases</h2>
            <a href="{{ route('student.record') }}" class="inline-flex items-center gap-1 font-mono text-[10.5px] text-blue hover:underline uppercase tracking-wider">
                Full record <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
        </div>
        <div class="bg-surface border border-edge overflow-x-auto">
            <table class="w-full text-[13px]">
                <thead>
                    <tr class="border-b border-edge">
                        <th class="eyebrow text-left px-5 py-3">Ref</th>
                        <th class="eyebrow text-left px-5 py-3">Case</th>
                        <th class="eyebrow text-left px-5 py-3">Status</th>
                        <th class="eyebrow text-left px-5 py-3">Filed</th>
                        <th class="eyebrow text-right px-5 py-3">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($completedEnrollments as $e)
                    <tr class="border-b border-edge last:border-0 hover:bg-base">
                        <td class="px-5 py-3 font-mono text-[11px] text-fg-3">CASE-{{ str_pad($e->forensicCase->id, 3, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-fg">{{ $e->forensicCase->title }}</p>
                            <p class="text-[11.5px] text-fg-3">{{ $e->forensicCase->incident_label }}</p>
                        </td>
                        <td class="px-5 py-3">
                            <span class="seal {{ $e->status === 'graded' ? 'seal-graded' : 'seal-open' }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                {{ $e->status === 'graded' ? 'Graded' : 'In review' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 font-mono text-[11px] text-fg-3">{{ $e->submitted_at?->format('d M Y') ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            @if($e->report?->marks !== null)
                                <span class="font-display font-semibold text-fg">{{ $e->report->marks }}</span><span class="text-fg-3 text-[11px]">/{{ $e->forensicCase->total_marks }}</span>
                            @else
                                <span class="text-fg-3">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
    @endif

</div>
@endsection
