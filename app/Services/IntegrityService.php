<?php
namespace App\Services;

use App\Models\CaseReport;

/**
 * Authorship integrity analysis.
 *
 * DESIGN NOTE — why this is not an "AI detector".
 *
 * Statistical AI-text detectors are unreliable. They produce false positives at
 * rates that make them unsafe for academic penalty decisions, and they misfire
 * disproportionately on writers whose first language is not English. OpenAI
 * withdrew its own classifier for poor accuracy.
 *
 * This service therefore reports OBSERVED BEHAVIOUR captured while the student
 * composed the report — paste size, typing volume, time on task, revision
 * count, and similarity against peer submissions on the same case. These are
 * facts, not inferences about authorship.
 *
 * It never blocks a submission and never renders a verdict. It surfaces
 * evidence so a human marker can ask an informed question.
 */
class IntegrityService
{
    /** A single paste larger than this is worth showing the marker. */
    private const LARGE_PASTE_CHARS = 220;

    /** Below this ratio of typed keystrokes to final length, most text arrived by paste. */
    private const LOW_TYPING_RATIO = 0.35;

    /** Composing a full report faster than this is unusually quick. */
    private const FAST_COMPOSE_SECONDS = 240;

    /** Similarity at or above this is worth a manual read of both reports. */
    private const SIMILARITY_THRESHOLD = 65;

    public function analyse(CaseReport $report): array
    {
        $body = $report->bodyText();
        $length = mb_strlen($body);

        $flags = [];

        // ── Copy-paste locations — the headline signal. Per-question answers
        // flag themselves the moment a rich/external paste is detected (see
        // Student\CaseController::saveAnswer); the report's own Investigation
        // Findings field is flagged the same way via paste_events below.
        // Evidence copied from inside the app never sets these flags — see
        // markInternalCopySource() in the shared layout.
        $answers = $report->enrollment?->answers()->with('question')->get() ?? collect();
        $externalLocations = $answers->values()
            ->map(fn ($a, $i) => $a->flagged_external_paste ? 'Q' . ($i + 1) : null)
            ->filter()->values()->all();

        $externalFindingsPastes = collect($report->paste_events ?? [])->where('external', true);
        if ($externalFindingsPastes->count() > 0) {
            $externalLocations[] = 'Investigation Findings';
        }

        // Rich/HTML clipboard content is a reliable signal when present, but a
        // plain-text paste (copied from an AI chat reply, a notes app, or a
        // webpage via "paste as plain text") carries no such marker and slips
        // past it entirely. When that happens, the aggregate composition
        // stats still tell the story: if almost all of the final text arrived
        // by paste while almost no time or keystrokes went into writing it,
        // that is worth flagging on its own — even without a confirmed
        // external source. This only ever applies to Investigation Findings,
        // since paste/keystroke/compose-time stats are tracked at the report
        // level, not per question.
        $patternLocations = [];
        if (! in_array('Investigation Findings', $externalLocations) && $length > 0 && $report->pasted_chars > 0) {
            $pastedShare = $report->pasted_chars / $length; // can exceed 1.0
            $lowKeystrokes = $report->keystroke_count <= 20
                || ($report->keystroke_count / max($length, 1)) < 0.05;
            $lowActiveTime = $report->compose_seconds <= 30;

            if ($pastedShare >= 0.8 && ($lowKeystrokes || $lowActiveTime)) {
                $patternLocations[] = 'Investigation Findings';
            }
        }

        $flaggedLocations = array_values(array_unique(array_merge($externalLocations, $patternLocations)));

        if (count($externalLocations) > 0) {
            $flags[] = [
                'code' => 'copy_paste_flagged',
                'severity' => 'review',
                'label' => 'Copy-paste flagged in ' . implode(', ', $externalLocations),
                'detail' => 'Formatted (HTML) clipboard content was detected on paste in ' . implode(', ', $externalLocations)
                    . ' — typical of copying from a website, an AI chat interface, or a document, not typing. Evidence copied from within the case workspace is exempt and never triggers this. '
                    . 'The pasted text was bracketed with a warning marker in the field rather than removed; check whether it still appears verbatim.',
            ];
        }

        if (count($patternLocations) > 0) {
            $flags[] = [
                'code' => 'pattern_paste_flagged',
                'severity' => 'review',
                'label' => 'Pasted without typing in ' . implode(', ', $patternLocations),
                'detail' => "No formatted clipboard content was detected, but {$report->pasted_chars} of the {$length} characters in this section arrived by paste, with almost no active typing time or keystrokes recorded — consistent with pasting from a plain-text source (an AI chat reply, notes, or \"paste as plain text\" from a webpage) rather than writing it.",
            ];
        }

        $copyPasteType = count($externalLocations) > 0 ? 'external' : (count($patternLocations) > 0 ? 'pattern' : 'none');

        $copyPaste = [
            'flagged' => count($flaggedLocations) > 0,
            'type' => $copyPasteType,
            'locations' => $flaggedLocations,
            'headline' => match ($copyPasteType) {
                'external' => 'Copy-paste detected',
                'pattern' => 'Unusual paste pattern',
                default => 'No copy-paste detected',
            },
            'sublabel' => match ($copyPasteType) {
                'external' => 'Formatted text was pasted from outside the case workspace. Ask the student to explain this section before grading.',
                'pattern' => 'No formatted (HTML) paste was detected, but the text arrived almost entirely by paste with very little active typing — consistent with a plain-text paste from outside the editor. Ask the student to explain this section before grading.',
                default => 'No paste in this report or its answers carried the formatting signal of an outside source, and composition volume looks typical.',
            },
        ];

        // ── Signal 1: pasted volume relative to the finished report ──
        if ($length > 0 && $report->pasted_chars > 0) {
            if ($report->pasted_chars >= $length) {
                // More was pasted across the whole editing session than
                // remains in the final text — the earlier percentage-of-final-
                // length framing goes over 100% and stops making sense here,
                // so say plainly that most of it was edited out or replaced.
                $flags[] = [
                    'code' => 'high_paste_share',
                    'severity' => 'note',
                    'label' => 'Substantial pasting during composition',
                    'detail' => "{$report->pasted_chars} characters were pasted in total while writing — more than the {$length}-character final report, so most of what was pasted was later edited out or replaced before submitting.",
                ];
            } else {
                $pastedShare = round($report->pasted_chars / $length * 100);
                if ($pastedShare >= 50) {
                    $flags[] = [
                        'code' => 'high_paste_share',
                        'severity' => 'review',
                        'label' => 'Majority of text arrived by paste',
                        'detail' => "{$pastedShare}% of the final report length was pasted rather than typed ({$report->pasted_chars} of {$length} characters).",
                    ];
                }
            }
        }

        // ── Signal 2: individual large paste events ──
        $largePastes = collect($report->paste_events ?? [])
            ->filter(fn ($e) => ($e['length'] ?? 0) >= self::LARGE_PASTE_CHARS);

        if ($largePastes->count() > 0) {
            $biggest = $largePastes->max('length');
            $flags[] = [
                'code' => 'large_paste',
                'severity' => 'note',
                'label' => $largePastes->count() . ' large paste ' . ($largePastes->count() === 1 ? 'event' : 'events'),
                'detail' => "Largest single paste was {$biggest} characters. Pasting from notes or the evidence panel is legitimate — the size is reported for context.",
            ];
        }

        // ── Signal 3: typing volume vs final length ──
        if ($length > 200 && $report->keystroke_count > 0) {
            $ratio = $report->keystroke_count / $length;
            if ($ratio < self::LOW_TYPING_RATIO) {
                $flags[] = [
                    'code' => 'low_typing_ratio',
                    'severity' => 'review',
                    'label' => 'Few keystrokes for the length submitted',
                    'detail' => sprintf(
                        'Recorded %d keystrokes for a %d character report (%.2f per character). Typing normally exceeds 1.0 because of corrections.',
                        $report->keystroke_count, $length, $ratio
                    ),
                ];
            }
        }

        // ── Signal 4: time on task ──
        if ($report->compose_seconds > 0 && $report->compose_seconds < self::FAST_COMPOSE_SECONDS && $length > 800) {
            $mins = round($report->compose_seconds / 60, 1);
            $flags[] = [
                'code' => 'fast_compose',
                'severity' => 'note',
                'label' => 'Short composition time',
                'detail' => "Report of {$length} characters was composed in {$mins} minutes of active editing.",
            ];
        }

        // ── Signal 5: no revision ──
        if ($report->revision_count <= 1 && $length > 800) {
            $flags[] = [
                'code' => 'no_revision',
                'severity' => 'note',
                'label' => 'Submitted without revision',
                'detail' => 'The report was saved once and submitted. Extended writing usually shows several revisions.',
            ];
        }

        // ── Signal 6: similarity against peers on the same case ──
        $similarity = $this->peerSimilarity($report);
        if ($similarity && $similarity['score'] >= self::SIMILARITY_THRESHOLD) {
            $flags[] = [
                'code' => 'peer_similarity',
                'severity' => 'review',
                'label' => "{$similarity['score']}% similar to another submission",
                'detail' => "Shares substantial phrasing with the report filed by {$similarity['student']}. Both reports should be read side by side.",
            ];
        }

        return [
            'copy_paste' => $copyPaste,
            'flags' => $flags,
            'similarity' => $similarity,
            'band' => $this->band($flags),
            'metrics' => [
                'length' => $length,
                'keystrokes' => $report->keystroke_count,
                'pasted_chars' => $report->pasted_chars,
                'paste_count' => $report->paste_count,
                'compose_minutes' => round($report->compose_seconds / 60, 1),
                'revisions' => $report->revision_count,
            ],
        ];
    }

    /**
     * Confidence band, phrased as how much marker attention is warranted.
     * Deliberately NOT phrased as a probability that AI was used.
     */
    private function band(array $flags): array
    {
        $review = collect($flags)->where('severity', 'review')->count();
        $notes = collect($flags)->where('severity', 'note')->count();

        if ($review >= 2) {
            return ['level' => 'discuss', 'label' => 'Worth discussing with the student',
                    'note' => 'Several signals suggest the text may not have been composed in the editor. Ask the student to talk through their findings.'];
        }
        if ($review === 1 || $notes >= 2) {
            return ['level' => 'context', 'label' => 'Read with context',
                    'note' => 'One or more signals are outside the typical range. This is common for legitimate reasons — draft written elsewhere, notes pasted in.'];
        }
        return ['level' => 'normal', 'label' => 'Nothing unusual recorded',
                'note' => 'Composition behaviour is within the typical range for this cohort.'];
    }

    /**
     * Compare against other submitted reports on the same case using shingled
     * word overlap (Jaccard on 5-grams). This is a direct textual measure and,
     * unlike AI detection, it is reproducible and explainable.
     */
    private function peerSimilarity(CaseReport $report): ?array
    {
        $caseId = $report->enrollment?->forensic_case_id;
        if (! $caseId) return null;

        $mine = $this->shingles($report->bodyText());
        if (count($mine) < 10) return null;

        $peers = CaseReport::whereHas('enrollment', fn ($q) => $q->where('forensic_case_id', $caseId))
            ->where('id', '!=', $report->id)
            ->whereIn('status', ['submitted', 'graded'])
            ->with('enrollment.student')
            ->get();

        $best = null;
        foreach ($peers as $peer) {
            $theirs = $this->shingles($peer->bodyText());
            if (count($theirs) < 10) continue;

            $intersect = count(array_intersect_key($mine, $theirs));
            $union = count($mine + $theirs);
            $score = $union > 0 ? (int) round($intersect / $union * 100) : 0;

            if (! $best || $score > $best['score']) {
                $best = [
                    'score' => $score,
                    'student' => $peer->enrollment?->student?->name ?? 'another student',
                    'report_id' => $peer->id,
                ];
            }
        }

        return $best;
    }

    /** Normalised 5-word shingles, keyed for fast set operations. */
    private function shingles(string $text, int $n = 5): array
    {
        $words = preg_split('/\s+/', mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text)), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        for ($i = 0; $i + $n <= count($words); $i++) {
            $out[implode(' ', array_slice($words, $i, $n))] = true;
        }
        return $out;
    }

    /** Persist the analysis so the lecturer view does not recompute on every load. */
    public function store(CaseReport $report): array
    {
        $result = $this->analyse($report);
        $report->update([
            'integrity_flags' => $result['flags'],
            'similarity_score' => $result['similarity']['score'] ?? null,
            'similar_to_report_id' => $result['similarity']['report_id'] ?? null,
        ]);
        return $result;
    }
}
