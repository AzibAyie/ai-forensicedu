@extends('layouts.app')
@section('title', 'Grade Report')
@section('page-title', 'Student Report')
@section('page-subtitle', $student->name . ' · ' . $forensicCase->title)

@section('content')
<div class="py-4 max-w-4xl" x-data="gradeReport()">

    {{-- Header Actions --}}
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('lecturer.report.index', $forensicCase) }}" class="text-sm text-white/70 hover:text-white">← Back to Reports</a>
        <div class="ml-auto flex gap-2">
            <button @click="runAI()" :disabled="aiLoading"
                class="btn-primary px-4 py-2.5 text-sm disabled:opacity-50 flex items-center gap-2">
                <span x-show="!aiLoading">🤖 AI Evaluate</span>
                <span x-show="aiLoading">⏳ Analysing...</span>
            </button>
            <a href="{{ route('lecturer.report.export-pdf', [$forensicCase, $enrollment]) }}"
                class="px-4 py-2.5 normal-case tracking-normal font-sans text-sm border border-white/30 text-white/85 hover:bg-white hover:text-fg transition rounded-lg">
                📄 Export PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">

        {{-- Report Content --}}
        <div class="col-span-2 space-y-4">

            {{-- Student Info --}}
            <div class="bg-surface  border border-edge  p-5">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-full bg-blue-soft flex items-center justify-center text-blue font-bold">
                        {{ strtoupper(substr($student->name, 0, 2)) }}
                    </div>
                    <div>
                        <h3 class="font-semibold text-fg">{{ $report->student_name }}</h3>
                        <p class="text-sm text-fg-2">{{ $report->student_id_number }} · {{ $report->program }}</p>
                    </div>
                    <div class="ml-auto text-right">
                        <p class="text-xs text-fg-3">Submitted</p>
                        <p class="text-sm font-medium text-fg">{{ $enrollment->submitted_at?->format('d M Y, H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Investigation Q&A --}}
            @if($answers->count())
            <div class="bg-surface  border border-edge  p-5">
                <h4 class="font-semibold text-fg mb-4">Investigation Answers</h4>
                <div class="space-y-4">
                    @foreach($answers as $a)
                    <div class="border {{ $a->flagged_external_paste ? 'border-red' : 'border-edge' }}  p-4">
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <p class="text-xs font-semibold text-fg-2">Q{{ $loop->iteration }}: {{ $a->question->question }} <span class="text-fg-3">({{ $a->question->marks }} marks)</span></p>
                            @if($a->flagged_external_paste)
                            <span class="seal seal-alert flex-shrink-0">Copy-paste flagged</span>
                            @endif
                        </div>
                        <p class="text-sm text-fg leading-relaxed">{{ $a->answer ?: '(no answer)' }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Investigation Findings --}}
            @php $findingsFlagged = $integrity && in_array('Investigation Findings', $integrity['copy_paste']['locations']); @endphp
            <div class="bg-surface border {{ $findingsFlagged ? 'border-red' : 'border-edge' }} p-5">
                <div class="flex items-start justify-between gap-3 mb-3">
                    <h4 class="font-semibold text-fg">🔍 Investigation Findings</h4>
                    @if($findingsFlagged)
                    <span class="seal seal-alert flex-shrink-0">Copy-paste flagged</span>
                    @endif
                </div>
                <div class="text-sm text-fg leading-relaxed whitespace-pre-wrap">{{ $report->findings }}</div>
            </div>

            {{-- Legacy report sections — only shown when a report predates the single-findings format --}}
            @foreach([
                ['field' => 'executive_summary', 'label' => '📌 Executive Summary'],
                ['field' => 'timeline_reconstruction', 'label' => '⏱️ Timeline Reconstruction'],
                ['field' => 'recommendations', 'label' => '🛡️ Recommendations'],
                ['field' => 'conclusion', 'label' => '📝 Conclusion'],
            ] as $section)
            @if($report->{$section['field']})
            <div class="bg-surface border border-edge p-5">
                <h4 class="font-semibold text-fg mb-3">{{ $section['label'] }}</h4>
                <div class="text-sm text-fg leading-relaxed whitespace-pre-wrap">{{ $report->{$section['field']} }}</div>
            </div>
            @endif
            @endforeach
        </div>

        {{-- Grading Panel --}}
        <div class="space-y-4">

            {{-- AI Feedback --}}
            <div x-show="aiResult" class="bg-blue-soft border border-blue  p-4">
                <p class="text-xs font-semibold text-blue mb-2">🤖 AI Evaluation</p>
                <div class="text-center mb-3">
                    <p class="text-3xl font-bold text-blue" x-text="aiResult?.overall_score + '%'"></p>
                    <p class="text-xs text-fg-2" x-text="'Grade: ' + (aiResult?.grade || '')"></p>
                </div>
                <p class="text-xs text-fg-2 mb-2 font-medium">Strengths:</p>
                <ul class="text-xs text-fg-2 space-y-1 mb-3">
                    <template x-for="s in aiResult?.strengths || []"><li class="flex gap-1"><span class="text-blue">✓</span><span x-text="s"></span></li></template>
                </ul>
                <p class="text-xs text-fg-2 mb-2 font-medium">Areas to improve:</p>
                <ul class="text-xs text-fg-2 space-y-1 mb-3">
                    <template x-for="w in aiResult?.weaknesses || []"><li class="flex gap-1"><span class="text-red">!</span><span x-text="w"></span></li></template>
                </ul>
                <p class="text-xs text-fg-2 leading-relaxed" x-text="aiResult?.detailed_feedback"></p>
                <button @click="applyAIScore()" class="mt-3 w-full text-xs bg-blue text-white py-1.5  hover:bg-blue-deep transition rounded-lg">
                    Apply AI Score ({{ '' }}<span x-text="aiResult?.overall_score"></span>%)
                </button>
            </div>

            @if($report->ai_feedback && !isset($aiResult))
            <div class="bg-blue-soft border border-blue  p-4">
                <p class="text-xs font-semibold text-blue mb-2">🤖 Previous AI Evaluation</p>
                @if($report->ai_suggested_marks !== null)
                <p class="text-sm text-blue font-bold mb-2">Suggested: {{ $report->ai_suggested_marks }}/{{ $forensicCase->total_marks }}</p>
                @endif
                <p class="text-xs text-fg-2 leading-relaxed">{{ $report->ai_feedback }}</p>
            </div>
            @endif


            {{-- ── AUTHORSHIP INTEGRITY ────────────────────────────── --}}
            @if($integrity)
            @php $copyPaste = $integrity['copy_paste']; $otherFlags = collect($integrity['flags'])->reject(fn($f) => $f['code'] === 'copy_paste_flagged'); @endphp
            <div class="bg-surface border border-edge">
                <div class="px-5 py-4 border-b border-edge">
                    <h4 class="font-display text-[14px] font-semibold text-fg">Authorship signals</h4>
                    <p class="text-[11.5px] text-fg-3 mt-0.5">Observed while the report was written. Evidence, not a verdict.</p>
                </div>

                {{-- Headline: where copy-paste was flagged, if anywhere --}}
                <div class="px-5 py-4 border-b border-edge {{ $copyPaste['flagged'] ? 'bg-red-soft' : '' }}">
                    <span class="seal {{ $copyPaste['flagged'] ? 'seal-alert' : 'seal-graded' }}">
                        {{ $copyPaste['flagged'] ? '⚠ Copy-paste detected' : '✓ No copy-paste detected' }}
                    </span>
                    @if($copyPaste['flagged'])
                    <p class="text-[13px] font-semibold text-fg mt-2.5">
                        Flagged in: {{ implode(', ', $copyPaste['locations']) }}
                    </p>
                    @endif
                    <p class="text-[12px] text-fg-2 leading-relaxed mt-1.5">{{ $copyPaste['sublabel'] }}</p>
                </div>

                {{-- Metrics --}}
                <div class="grid grid-cols-3 gap-px bg-edge border-b border-edge">
                    @foreach([
                        ['Length', number_format($integrity['metrics']['length']).' ch'],
                        ['Active time', $integrity['metrics']['compose_minutes'].' min'],
                        ['Revisions', $integrity['metrics']['revisions']],
                    ] as $m)
                    <div class="bg-surface px-3 py-2.5">
                        <p class="eyebrow mb-1">{{ $m[0] }}</p>
                        <p class="font-mono text-[12.5px] text-fg">{{ $m[1] }}</p>
                    </div>
                    @endforeach
                </div>

                {{-- Other, secondary composition signals (typing ratio, compose time,
                     revisions, peer similarity) — collapsed by default so the
                     copy-paste headline above stays the focus. --}}
                <details class="border-b border-edge">
                    <summary class="px-5 py-3 text-[12px] font-medium text-fg-2 cursor-pointer select-none">
                        {{ $otherFlags->count() }} other composition {{ $otherFlags->count() === 1 ? 'signal' : 'signals' }}
                    </summary>
                    @forelse($otherFlags as $flag)
                    <div class="px-5 py-3.5 border-t border-edge">
                        <div class="flex items-start gap-2.5">
                            <span class="seal {{ $flag['severity'] === 'review' ? 'seal-alert' : 'seal-draft' }} mt-0.5 flex-shrink-0">
                                {{ $flag['severity'] === 'review' ? 'Review' : 'Note' }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-[12.5px] font-medium text-fg leading-snug">{{ $flag['label'] }}</p>
                                <p class="text-[11.5px] text-fg-2 leading-relaxed mt-1">{{ $flag['detail'] }}</p>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-5 py-4 border-t border-edge">
                        <p class="text-[12px] text-fg-2">Nothing outside the typical range.</p>
                    </div>
                    @endforelse
                </details>

                <div class="px-5 py-3 bg-base">
                    <p class="text-[11px] text-fg-3 leading-relaxed">
                        These figures are reported by the browser and describe how text entered the editor.
                        They do not identify the author. Treat them as a prompt to ask the student about their work,
                        never as proof of misconduct on their own.
                    </p>
                </div>
            </div>
            @endif

            {{-- Answer sheet --}}
            @if($report->answer_pdf_path)
            <div class="bg-surface border border-edge px-5 py-4">
                <p class="eyebrow mb-1.5">Attached answer sheet</p>
                <p class="text-[13px] text-fg mb-2.5">{{ $report->answer_pdf_name }}</p>
                <a href="{{ route('lecturer.report.answer', [$forensicCase, $enrollment]) }}" class="btn-ghost px-3 py-2 inline-block">
                    Download PDF
                </a>
            </div>
            @endif

            {{-- Grade Form --}}
            <div class="bg-surface  border border-edge  p-5">
                <h4 class="font-semibold text-fg mb-4">
                    {{ $enrollment->status === 'graded' ? 'Update Grade' : 'Grade This Report' }}
                </h4>
                <form method="POST" action="{{ route('lecturer.report.grade', [$forensicCase, $enrollment]) }}">
                    @csrf @method('PATCH')
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-fg-2 mb-1">
                                Score (out of {{ $forensicCase->total_marks }}) *
                            </label>
                            <input type="number" name="marks" id="marksInput"
                                value="{{ $report->marks ?? $report->ai_suggested_marks ?? '' }}"
                                min="0" max="{{ $forensicCase->total_marks }}" required
                                class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-fg-2 mb-1">Feedback to Student *</label>
                            <textarea name="lecturer_feedback" required rows="6"
                                class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue resize-none"
                                placeholder="Write your feedback...">{{ $report->lecturer_feedback }}</textarea>
                        </div>
                        <button type="submit" class="w-full bg-black hover:bg-blue text-white py-2  text-sm font-semibold transition rounded-lg">
                            ✅ {{ $enrollment->status === 'graded' ? 'Update Grade' : 'Submit Grade' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function gradeReport() {
    return {
        aiLoading: false,
        aiResult: null,

        async runAI() {
            this.aiLoading = true;
            try {
                const res = await fetch('{{ route('lecturer.report.ai-evaluate', [$forensicCase, $enrollment]) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Content-Type': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    this.aiResult = data.evaluation;
                } else {
                    alert(data.message || 'AI evaluation failed.');
                }
            } catch(e) {
                alert('Error contacting AI service.');
            }
            this.aiLoading = false;
        },

        applyAIScore() {
            if (this.aiResult?.overall_score !== undefined) {
                const totalMarks = {{ $forensicCase->total_marks }};
                const score = Math.round((this.aiResult.overall_score / 100) * totalMarks);
                document.getElementById('marksInput').value = score;
            }
        }
    }
}
</script>
@endpush
