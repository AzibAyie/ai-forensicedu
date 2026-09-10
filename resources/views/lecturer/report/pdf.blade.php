<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Forensic Report — {{ $report->student_name }}</title>
<style>
    @page { size: A4 portrait; margin: 0; }

    * { box-sizing: border-box; }
    body {
        margin: 0; padding: 0;
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 7.6pt; line-height: 1.45;
        color: #0B0D10;
        width: 210mm;
    }

    /* Narrower than the true 210mm page width on purpose — dompdf's child-width
       math for elements inside .sheet runs several mm past whatever padding is
       declared here (verified by reading the actual painted glyph coordinates,
       not just visual inspection), so the box is shrunk up front to leave real
       clearance for the outer frame border below rather than chasing that gap
       per-element. */
    .sheet { width: 200mm; padding: 9mm 8mm 14mm 8mm; border: 1.25pt solid #0B0D10; }

    /* ── Masthead ───────────────────────────────── */
    /* Floats, not table columns or position:absolute — verified against actual
       painted glyph coordinates (not just visual inspection): dompdf's
       `position:absolute; right:0` anchors to the page edge rather than this
       container, which silently clipped the ref block off the page no matter
       how the surrounding padding/width was tuned. Floats are one of the
       CSS layout mechanisms dompdf implements reliably. */
    .masthead { border: 1pt solid #0B0D10; padding: 3mm 3mm 3mm 3mm; margin-bottom: 3.5mm; }
    .masthead::after { content: ""; display: block; clear: both; }
    .mast-left { float: left; width: 62%; }
    .mast-mark {
        display: inline-block; width: 5.5mm; height: 5.5mm; background: #1749D1;
        color: #fff; font-weight: bold; font-size: 7pt; text-align: center; line-height: 5.5mm;
        margin-right: 2mm; vertical-align: middle;
    }
    .mast-title { font-size: 14pt; font-weight: bold; letter-spacing: -0.4pt; line-height: 1; display: inline-block; vertical-align: middle; }
    .mast-sub { font-size: 6.6pt; color: #4A515C; padding-top: 1.6mm; }
    .mast-ref {
        float: right; width: 34%; margin-right: 15mm;
        text-align: right; font-size: 6pt; color: #4A515C;
        letter-spacing: 0.6pt; line-height: 1.7;
    }
    .stamp {
        display: inline-block; border: 1pt solid #0E2F8C; background: #E7ECFB;
        color: #0E2F8C; font-size: 6pt; font-weight: bold;
        letter-spacing: 1pt; padding: 1.2mm 2.4mm;
    }
    .stamp-graded { border-color: #0E2F8C; background: #E7ECFB; color: #0E2F8C; }

    /* ── Identity strip ─────────────────────────── */
    .identity { width: 100%; border-collapse: collapse; margin-bottom: 3.5mm; }
    .identity td {
        border: 0.5pt solid #E0E3E9; padding: 1.8mm 2.4mm; width: 25%;
        vertical-align: top;
    }
    .id-label { font-size: 5.4pt; color: #858C97; letter-spacing: 0.7pt; text-transform: uppercase; }
    .id-value { font-size: 8pt; font-weight: bold; padding-top: 0.7mm; }

    /* ── Findings — the primary section, full width ─ */
    .findings-block {
        border: 1.25pt solid #0B0D10; padding: 4mm 8mm 4mm 4.5mm; margin-bottom: 3.5mm;
    }
    .findings-head {
        font-size: 6.4pt; font-weight: bold; letter-spacing: 1.2pt; text-transform: uppercase;
        color: #1749D1; margin-bottom: 1.8mm;
    }
    .findings-body { font-size: 7.4pt; line-height: 1.5; color: #23282F; text-align: left; word-wrap: break-word; overflow-wrap: break-word; }
    .trunc-note { font-size: 5.6pt; color: #858C97; font-style: italic; margin-top: 1.6mm; }

    /* ── Legacy / additional notes ──────────────── */
    .legacy-wrap { margin-bottom: 3.5mm; padding-right: 6mm; }
    .legacy-head {
        font-size: 5.6pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase;
        color: #858C97; margin-bottom: 1.6mm;
    }
    .legacy-sec { margin-bottom: 2.2mm; }
    .legacy-sec-head { font-size: 5.8pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.6pt; color: #4A515C; }
    .legacy-sec-body { font-size: 6.8pt; color: #4A515C; text-align: left; padding-top: 0.6mm; word-wrap: break-word; overflow-wrap: break-word; }

    /* ── Two-column row: Q&A + Assessment ───────── */
    .cols { width: 100%; border-collapse: collapse; }
    .cols > tbody > tr > td { vertical-align: top; }
    .col-l { width: 58%; padding-right: 4mm; }
    .col-r { width: 40%; border-left: 0.5pt solid #E0E3E9; padding-left: 4mm; padding-right: 10mm; }

    .sec { margin-bottom: 3.2mm; }
    .sec-head {
        font-size: 6pt; font-weight: bold; letter-spacing: 1pt; text-transform: uppercase;
        color: #0B0D10; border-bottom: 0.5pt solid #0B0D10;
        padding-bottom: 1mm; margin-bottom: 1.6mm;
    }

    /* ── Q&A — each question is its own fully-bordered box ─────── */
    .qa { margin-bottom: 2.6mm; border: 0.75pt solid #0B0D10; padding: 2.2mm 2.6mm; }
    .qa-q { font-size: 6.6pt; font-weight: bold; color: #0B0D10; }
    .qa-m { font-size: 5.6pt; color: #858C97; }
    .qa-a { font-size: 6.9pt; color: #414852; padding-top: 0.5mm; text-align: left; word-wrap: break-word; overflow-wrap: break-word; }

    /* ── Assessment ─────────────────────────────── */
    .score-block {
        border: 1pt solid #0B0D10; padding: 2.6mm; text-align: center; margin-bottom: 3mm;
    }
    .score-num { font-size: 24pt; font-weight: bold; line-height: 0.95; }
    .score-den { font-size: 8pt; color: #4A515C; }
    .score-lbl { font-size: 5.6pt; letter-spacing: 1pt; text-transform: uppercase; color: #858C97; padding-top: 1.2mm; }
    .grade-bar { height: 2.4mm; background: #E0E3E9; margin-top: 2.4mm; }
    .grade-fill { height: 2.4mm; background: #1749D1; }

    .fb { border: 0.75pt solid #0B0D10; padding: 2.2mm 2.4mm; margin-bottom: 2.6mm; }
    .fb-ai { border-color: #0E2F8C; }
    .fb-head { font-size: 5.6pt; font-weight: bold; letter-spacing: 0.8pt; text-transform: uppercase; color: #4A515C; padding-bottom: 0.8mm; }
    .fb-text { font-size: 6.7pt; color: #333A44; text-align: left; }

    /* ── Footer — fixed so it repeats correctly on every page ────── */
    .footer {
        position: fixed; bottom: 5mm; left: 10mm; right: 10mm;
        border-top: 0.5pt solid #E0E3E9; padding-top: 1.6mm;
        font-size: 5.4pt; color: #858C97; letter-spacing: 0.4pt;
    }
    .footer table { width: 100%; border-collapse: collapse; }
    .footer td:last-child { text-align: right; }
</style>
</head>
<body>
<div class="sheet">

    {{-- MASTHEAD --}}
    <div class="masthead">
        <div class="mast-left">
            <span class="mast-mark">FE</span><span class="mast-title">Forensic Investigation Report</span>
            <div class="mast-sub">{{ Str::limit($forensicCase->title . ' — ' . $forensicCase->incident_label, 70) }}</div>
        </div>
        <div class="mast-ref">
            CASE-{{ str_pad($forensicCase->id, 3, '0', STR_PAD_LEFT) }} &nbsp;·&nbsp;
            {{ strtoupper($forensicCase->difficulty) }}<br>
            ISSUED {{ strtoupper(now()->format('d M Y')) }}<br>
            <span class="stamp {{ $report->marks !== null ? 'stamp-graded' : '' }}">
                {{ $report->marks !== null ? 'GRADED' : 'UNDER REVIEW' }}
            </span>
        </div>
    </div>

    {{-- IDENTITY --}}
    <table class="identity"><tr>
        <td><div class="id-label">Investigator</div><div class="id-value">{{ $report->student_name }}</div></td>
        <td><div class="id-label">Student ID</div><div class="id-value">{{ $report->student_id_number }}</div></td>
        <td><div class="id-label">Programme</div><div class="id-value">{{ Str::limit($report->program, 28) }}</div></td>
        <td><div class="id-label">Filed</div><div class="id-value">{{ $enrollment->submitted_at?->format('d M Y, H:i') ?? '—' }}</div></td>
    </tr></table>

    {{-- Q&A + ASSESSMENT — questions/answers on the left, marks and feedback
         on the right, directly under the identity strip. --}}
    <table class="cols"><tr>

        {{-- LEFT: Investigation Responses --}}
        <td class="col-l">
            @if($answers->count())
            <div class="sec">
                <div class="sec-head">Investigation Responses</div>
                @foreach($answers as $a)
                <div class="qa">
                    <div class="qa-q">Q{{ $loop->iteration }}. {{ $a->question->question }}
                        <span class="qa-m">[{{ $a->question->marks }}]</span>
                    </div>
                    <div class="qa-a">{{ Str::limit($a->answer ?: 'No response recorded.', 260) }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </td>

        {{-- RIGHT: Assessment --}}
        <td class="col-r">

            @if($report->marks !== null)
            @php $pct = round($report->marks / max($forensicCase->total_marks, 1) * 100); @endphp
            <div class="score-block">
                <div class="score-num">{{ $report->marks }}<span class="score-den">/{{ $forensicCase->total_marks }}</span></div>
                <div class="score-lbl">Final Assessment — {{ $pct }}%</div>
                <div class="grade-bar"><div class="grade-fill" style="width: {{ $pct }}%"></div></div>
            </div>
            @endif

            @if($report->lecturer_feedback)
            <div class="fb">
                <div class="fb-head">Examiner Feedback</div>
                <div class="fb-text">{{ $report->lecturer_feedback }}</div>
            </div>
            @endif

            @if($report->ai_feedback)
            <div class="fb fb-ai">
                <div class="fb-head">AI Analysis
                    @if($report->ai_suggested_marks !== null)· suggested {{ $report->ai_suggested_marks }}%@endif
                </div>
                <div class="fb-text">{{ Str::limit($report->ai_feedback, 620) }}</div>
            </div>
            @endif

        </td>
    </tr></table>

    {{-- INVESTIGATION FINDINGS — full width, below the Q&A/assessment row.
         Capped so one unusually long submission can't push the whole report
         past a single page; the full text always remains in the system. --}}
    @php $findingsLimit = 1300; @endphp
    <div class="findings-block">
        <div class="findings-head">Investigation Findings</div>
        <div class="findings-body">{{ Str::limit($report->findings, $findingsLimit) }}</div>
        @if(mb_strlen($report->findings) > $findingsLimit)
        <div class="trunc-note">Truncated for print — full text available in the system.</div>
        @endif
    </div>

    {{-- Legacy narrative fields, only present on reports filed before the single-findings format --}}
    @php
        $legacy = collect([
            'Executive Summary' => $report->executive_summary,
            'Timeline Reconstruction' => $report->timeline_reconstruction,
            'Security Recommendations' => $report->recommendations,
            'Conclusion' => $report->conclusion,
        ])->filter();
    @endphp
    @if($legacy->count())
    <div class="legacy-wrap">
        <div class="legacy-head">Additional Notes (filed with report)</div>
        @foreach($legacy as $label => $text)
        <div class="legacy-sec">
            <div class="legacy-sec-head">{{ $label }}</div>
            <div class="legacy-sec-body">{{ Str::limit($text, 400) }}</div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- FOOTER --}}
    <div class="footer">
        <table><tr>
            <td>AI-FORENSICEDU · DIGITAL FORENSICS EDUCATION PLATFORM</td>
            <td>CONFIDENTIAL — ACADEMIC USE ONLY · PAGE 1 OF 1</td>
        </tr></table>
    </div>

</div>
</body>
</html>
