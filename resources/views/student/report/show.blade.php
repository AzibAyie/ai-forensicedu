@extends('layouts.app')
@section('title', 'Case Report')
@section('eyebrow', 'Investigation')
@section('page-title', 'Case Report')

@section('content')
<div class="pt-6 max-w-4xl" x-data="reportEditor()" x-init="init()">

    {{-- Submitted state --}}
    @if($enrollment->isSubmitted())
    <div class="bg-blue-soft border-l-2 border-blue px-6 py-5 mb-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <span class="seal seal-graded mb-2">Filed</span>
                <p class="font-display text-[16px] font-semibold text-fg">Report submitted</p>
                <p class="text-[13px] text-fg-2 mt-1">
                    Filed {{ $enrollment->submitted_at?->format('d M Y, H:i') }}. Your lecturer will review it.
                </p>
            </div>
            @if($report?->marks !== null)
            <div class="text-right flex-shrink-0">
                <p class="font-display text-[32px] font-semibold text-blue leading-none">
                    {{ $report->marks }}<span class="text-[16px] text-fg-3">/{{ $forensicCase->total_marks }}</span>
                </p>
                <p class="eyebrow mt-1.5">Final score</p>
            </div>
            @endif
        </div>

        @if($report?->lecturer_feedback)
        <div class="mt-4 bg-surface border border-edge p-4">
            <p class="eyebrow mb-1.5">Examiner feedback</p>
            <p class="text-[13px] text-fg-2 leading-relaxed">{{ $report->lecturer_feedback }}</p>
        </div>
        @endif

        @if($report?->answer_pdf_name)
        <p class="font-mono text-[11px] text-fg-3 mt-3">Answer sheet attached: {{ $report->answer_pdf_name }}</p>
        @endif
    </div>
    @endif

    @if(!$enrollment->isSubmitted())
    <form method="POST" action="{{ route('student.report.submit', $forensicCase) }}"
        enctype="multipart/form-data" id="reportForm" @submit="stampTelemetry()">
        @csrf

        {{-- Hidden telemetry --}}
        <input type="hidden" name="_keystrokes" x-ref="keystrokes">
        <input type="hidden" name="_compose_seconds" x-ref="composeSeconds">
        <input type="hidden" name="_paste_events" x-ref="pasteEvents">

        <div class="space-y-5">

            {{-- Unanswered-question warning — catch this before submission, not after. --}}
            @php $totalQuestions = $forensicCase->questions()->count(); @endphp
            @if($totalQuestions > 0 && $answers->count() < $totalQuestions)
            <div class="bg-red-soft border border-red px-5 py-4">
                <p class="text-[13px] font-semibold text-red">
                    {{ $answers->count() === 0 ? "You haven't answered any investigation questions yet" : "You've answered {$answers->count()} of {$totalQuestions} investigation questions" }}
                </p>
                <p class="text-[12px] text-fg-2 mt-1">
                    Go back to the <a href="{{ route('student.case.show', $forensicCase) }}" class="text-blue hover:underline font-medium">case page</a> and answer the remaining questions before submitting — your lecturer sees exactly how many were completed.
                </p>
            </div>
            @endif

            {{-- Question sheet --}}
            @if($forensicCase->question_pdf_path)
            <section class="bg-surface border border-edge px-6 py-5">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="eyebrow mb-1.5">Question sheet</p>
                        <p class="font-display text-[14px] font-semibold text-fg">{{ $forensicCase->question_pdf_name }}</p>
                        <p class="text-[12.5px] text-fg-2 mt-1">Download, answer it, and attach your completed sheet below.</p>
                    </div>
                    <a href="{{ route('student.case.questions', $forensicCase) }}" class="btn-primary text-[13px] px-4 py-2.5 flex-shrink-0">
                        Download PDF
                    </a>
                </div>
            </section>
            @endif

            {{-- Identity --}}
            <section class="bg-surface border border-edge">
                <div class="px-6 py-4 border-b border-edge">
                    <h3 class="font-display text-[14px] font-semibold text-fg">Investigator details</h3>
                </div>
                <div class="px-6 py-5 grid grid-cols-3 gap-4">
                    <div class="col-span-2">
                        <label class="eyebrow block mb-1.5">Full name</label>
                        <input type="text" name="student_name" class="fld" required
                            value="{{ old('student_name', $report?->student_name ?? auth()->user()->name) }}">
                    </div>
                    <div>
                        <label class="eyebrow block mb-1.5">Student ID</label>
                        <input type="text" name="student_id_number" class="fld" required
                            value="{{ old('student_id_number', $report?->student_id_number ?? auth()->user()->student_id) }}">
                    </div>
                    <div class="col-span-3">
                        <label class="eyebrow block mb-1.5">Programme</label>
                        <input type="text" name="program" class="fld" required
                            value="{{ old('program', $report?->program ?? auth()->user()->program) }}">
                    </div>
                </div>
            </section>

            {{-- Findings carried over — this is the student's own prior work, so
                 copying it into the findings box below is never flagged. --}}
            @if($answers->count())
            <section class="bg-blue-soft border border-edge" x-init="markInternalCopySource($el)">
                <div class="px-6 py-4 border-b border-edge">
                    <h3 class="font-display text-[14px] font-semibold text-fg">Your investigation answers</h3>
                    <p class="text-[12px] text-fg-2 mt-0.5">Carried over from the case. Use these to write the report below.</p>
                </div>
                <div class="px-6 py-5 space-y-3">
                    @foreach($answers as $a)
                    <div class="bg-surface border border-edge p-4">
                        <p class="text-[12px] font-medium text-fg-2 mb-1.5">Q{{ $loop->iteration }}: {{ $a->question->question }}</p>
                        <p class="text-[13px] text-fg leading-relaxed">{{ $a->answer ?: '(not answered)' }}</p>
                    </div>
                    @endforeach
                </div>
            </section>
            @endif

            {{-- External paste warning --}}
            <div x-show="externalPasteWarning" x-cloak x-transition
                class="bg-red text-white px-5 py-3.5 flex items-start gap-2.5 rounded-lg">
                <span class="font-mono text-[10px] uppercase tracking-[0.14em] border border-white/40 px-2 py-1 flex-shrink-0 mt-0.5">Flagged</span>
                <p class="text-[13px] leading-relaxed">
                    That paste looked like formatted text from a website or document, not something typed here — it's been
                    bracketed in the field below and noted on your submission. Please rewrite it in your own words before submitting.
                </p>
            </div>

            {{-- Investigation findings — the one narrative section --}}
            <section class="bg-surface border border-edge px-6 py-5">
                <label class="font-display text-[14px] font-semibold text-fg block mb-1">Investigation findings</label>
                <p class="text-[12px] text-fg-3 mb-3">
                    Summarise what your investigation found: what happened, what the evidence showed, and your overall assessment.
                    Use your answers above as the basis for this. Minimum 100 characters.
                </p>
                <textarea name="findings" rows="10" required minlength="100" class="fld resize-y"
                    placeholder="Based on the evidence and my answers above, this investigation found that…"
                    @keydown="keystrokes++"
                    @paste="recordPaste($event, 'findings')"
                    @input="touch()">{{ old('findings', $report?->findings) }}</textarea>
            </section>

            {{-- Answer sheet upload --}}
            @if($forensicCase->question_pdf_path)
            <section class="bg-surface border border-edge px-6 py-5">
                <label class="font-display text-[14px] font-semibold text-fg block mb-1">Completed answer sheet</label>
                <p class="text-[12px] text-fg-3 mb-3">Attach your answers to the question sheet as a PDF. Maximum 10 MB.</p>
                <input type="file" name="answer_pdf" accept="application/pdf"
                    class="fld file:mr-3 file:border-0 file:bg-blue file:text-base file:px-3 file:py-1.5 file:text-[12px] file:font-semibold file:cursor-pointer">
            </section>
            @endif

            {{-- Submit --}}
            <section class="bg-surface border border-edge px-6 py-5">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-[13px] font-medium text-fg">Ready to submit?</p>
                        <p class="text-[12px] text-fg-3 mt-0.5">You cannot edit after submitting.</p>
                        <p class="font-mono text-[10.5px] text-fg-3 mt-2" x-text="statusLine()"></p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="saveDraft()" class="btn-ghost px-4 py-2.5" x-text="draftLabel"></button>
                        @php
                            $confirmMsg = ($totalQuestions > 0 && $answers->count() < $totalQuestions)
                                ? "You've only answered {$answers->count()} of {$totalQuestions} investigation questions. Submit anyway? You will not be able to edit it afterwards."
                                : 'Submit this report? You will not be able to edit it afterwards.';
                        @endphp
                        <button type="submit" class="btn-primary text-[13px] px-6 py-2.5"
                            onclick="return confirm(@js($confirmMsg))">
                            Submit report
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </form>
    @endif
</div>
@endsection

@push('scripts')
<script>
function reportEditor() {
    return {
        keystrokes: 0,
        pastes: [],
        startedAt: Date.now(),
        activeSeconds: 0,
        lastTouch: Date.now(),
        draftLabel: 'Save draft',
        autosaveTimer: null,

        init() {
            // Count only time where the student is actually editing, so leaving
            // the tab open overnight doesn't inflate the figure.
            setInterval(() => {
                if (Date.now() - this.lastTouch < 60000) this.activeSeconds += 5;
            }, 5000);
        },

        touch() {
            this.lastTouch = Date.now();
            clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => this.saveDraft(true), 4000);
        },

        externalPasteWarning: false,

        recordPaste(e, field) {
            const { text, isRich } = extractPasteInfo(e);
            if (text.length === 0) return;

            if (isRich) {
                // Formatted clipboard content — the telltale sign of copying out
                // of a rendered webpage or a word processor rather than typing.
                // Keep the student's words, but flag them unmistakably in place.
                e.preventDefault();
                insertWithExternalMarker(e.target, text);
                this.externalPasteWarning = true;
                setTimeout(() => this.externalPasteWarning = false, 7000);
            }

            this.pastes.push({
                field: field,
                length: text.length,
                at: new Date().toISOString(),
                external: isRich,
            });

            if (isRich) {
                // Persist the moment it happens, rather than waiting for the
                // usual autosave debounce — this record lives in telemetry the
                // student never sees or edits, so deleting the bracketed text
                // (or the whole paste) from the field afterwards changes
                // nothing here. The lecturer's integrity panel reads this, not
                // the current field contents.
                this.saveDraft(true);
            } else {
                this.touch();
            }
        },

        statusLine() {
            const mins = Math.round(this.activeSeconds / 60);
            const externalCount = this.pastes.filter(p => p.external).length;
            const flag = externalCount > 0 ? ` · ${externalCount} flagged as external` : '';
            return `${this.keystrokes} keystrokes · ${mins} min active · ${this.pastes.length} paste events${flag}`;
        },

        stampTelemetry() {
            this.$refs.keystrokes.value = this.keystrokes;
            this.$refs.composeSeconds.value = this.activeSeconds;
            this.$refs.pasteEvents.value = JSON.stringify(this.pastes);
            return true;
        },

        async saveDraft(silent = false) {
            this.stampTelemetry();
            const form = document.getElementById('reportForm');
            if (!form) return;
            const data = new FormData(form);
            data.delete('answer_pdf');

            if (!silent) this.draftLabel = 'Saving…';
            try {
                const res = await fetch('{{ route('student.report.draft', $forensicCase) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: data,
                });
                const json = await res.json();
                this.draftLabel = json.success ? 'Draft saved' : 'Save failed';
            } catch (e) {
                this.draftLabel = 'Save failed';
            }
            setTimeout(() => this.draftLabel = 'Save draft', 2500);
        },
    }
}
</script>
@endpush
