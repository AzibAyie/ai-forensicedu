@extends('layouts.app')
@section('title', 'Case Board')
@section('eyebrow', 'Investigation')
@section('page-title', 'Case Board')

@section('content')
<div class="pt-6 space-y-8">

    {{-- ACTIVE CASE — the hero. Only shows when there is one. --}}
    @if($hasActiveCase && $currentEnrollment)
    @php $ac = $currentEnrollment->forensicCase; @endphp
    <section class="bg-black text-white rounded-2xl overflow-hidden">
        <div class="px-7 py-6">
            <div class="flex items-start justify-between gap-6 mb-5">
                <div class="min-w-0">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="seal seal-active">Under Investigation</span>
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
                    class="bg-blue text-white font-semibold text-[13px] px-5 py-2.5 hover:bg-white hover:text-blue transition rounded-lg">
                    Resume investigation
                </a>
                <a href="{{ route('student.report.show', $ac) }}"
                    class="border border-white/25 text-white text-[13px] px-5 py-2.5 hover:bg-white/10 transition">
                    Write report
                </a>
                <p class="font-mono text-[10.5px] text-white/50 ml-auto">
                    Opened {{ $currentEnrollment->started_at?->diffForHumans() }}
                </p>
            </div>
        </div>
    </section>
    @endif

    {{-- QUICK STATS --}}
    <section>
        <div class="grid grid-cols-2 lg:grid-cols-4 border-t border-l border-edge rounded-xl overflow-hidden">
            @foreach([
                ['Cases opened', $stats['total_cases'], null],
                ['Reports filed', $stats['completed'], null],
                ['Average score', $stats['avg_score'] > 0 ? number_format($stats['avg_score'], 0).'%' : '—', null],
                ['Current streak', $stats['streak'].'', 'consecutive passing grades'],
            ] as $s)
            <div class="bg-surface border-r border-b border-edge px-5 py-4">
                <p class="eyebrow mb-2">{{ $s[0] }}</p>
                <p class="font-display text-[26px] font-semibold text-fg leading-none">{{ $s[1] }}</p>
                @if($s[2])<p class="text-[11px] text-fg-3 mt-1.5">{{ $s[2] }}</p>@endif
            </div>
            @endforeach
        </div>
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
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-px bg-edge border border-edge">
            @foreach($availableCases as $case)
            <article class="bg-surface flex flex-col {{ $hasActiveCase ? 'opacity-55' : 'card-interactive' }}" x-data="{ pwd: false }">

                {{-- Folder tab header --}}
                <div class="px-5 pt-4 pb-3 border-b border-edge">
                    <div class="flex items-center justify-between mb-2.5">
                        <span class="font-mono text-[10px] text-fg-3 tracking-wider">
                            CASE-{{ str_pad($case->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                        <span class="seal {{ $case->is_locked ? 'seal-draft' : 'seal-open' }}">
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
                <div class="px-5 py-3 border-t border-edge flex items-center gap-4 font-mono text-[10.5px] text-fg-3">
                    <span class="diff diff-{{ $case->difficulty }}">{{ $case->difficulty }}</span>
                    <span class="ml-auto">{{ $case->expected_duration }}min</span>
                    <span>{{ $case->questions->count() }}Q</span>
                    <span>{{ $case->total_marks }}pts</span>
                </div>

                {{-- Action --}}
                <div class="px-5 pb-5 pt-1">
                    @if($hasActiveCase)
                        <p class="font-mono text-[10.5px] text-fg-3 text-center py-2.5 border border-edge">Locked — case in progress</p>
                    @elseif($case->is_locked)
                        <div x-show="!pwd">
                            <button @click="pwd = true"
                                class="w-full bg-black text-white text-[13px] font-medium py-2.5 hover:bg-blue transition rounded-lg">
                                Enter case password
                            </button>
                        </div>
                        <form x-show="pwd" x-cloak x-transition method="POST" action="{{ route('student.case.unlock', $case) }}" class="flex">
                            @csrf
                            <input type="password" name="password" required autofocus placeholder="Password"
                                class="flex-1 min-w-0 border border-edge border-r-0 px-3 py-2.5 text-[13px] font-mono focus:outline-none focus:border-blue !rounded-r-none">
                            <button type="submit" class="bg-blue text-white font-semibold text-[13px] px-4 hover:bg-blue-deep transition rounded-lg !rounded-l-none">
                                Unlock
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('student.case.unlock', $case) }}">
                            @csrf
                            <button type="submit"
                                class="w-full bg-blue text-white text-[13px] font-semibold py-2.5 hover:bg-surface hover:text-fg transition rounded-lg">
                                Open case file
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
            <a href="{{ route('student.record') }}" class="font-mono text-[10.5px] text-blue hover:underline uppercase tracking-wider">
                Full record →
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
