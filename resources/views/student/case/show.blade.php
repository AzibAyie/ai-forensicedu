@extends('layouts.app')
@section('title', $forensicCase->title)
@section('page-title', $forensicCase->title)
@section('page-subtitle', $forensicCase->incident_label . ' · ' . ucfirst($forensicCase->difficulty))

@section('content')
<div class="py-4" x-data="caseInvestigation()" x-init="init()">

    {{-- Progress bar --}}
    <div class="bg-surface  border border-edge p-4 mb-5 " :class="{ 'flash-once': justSaved }">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-fg">Investigation Progress</span>
            <span class="text-sm font-bold text-blue" x-text="progress + '%'"></span>
        </div>
        <div class="bg-edge rounded-full h-2.5">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-2.5 rounded-full progress-bar" :style="`width: ${progress}%`"></div>
        </div>
        <div class="flex items-center gap-4 mt-2 text-xs text-fg-3">
            <span>⏱ Expected: {{ $forensicCase->expected_duration }} min</span>
            <span>📝 {{ $forensicCase->questions->count() }} questions</span>
            <span>🏆 {{ $forensicCase->total_marks }} total marks</span>
            <span>Started: {{ $enrollment->started_at?->format('d M Y, H:i') }}</span>
        </div>
    </div>

    {{-- External paste warning --}}
    <div x-show="externalPasteWarning" x-cloak x-transition
        class="bg-red text-white px-5 py-3.5 flex items-start gap-2.5 rounded-lg mb-5">
        <span class="font-mono text-[10px] uppercase tracking-[0.14em] border border-white/40 px-2 py-1 flex-shrink-0 mt-0.5">Flagged</span>
        <p class="text-[13px] leading-relaxed">
            That paste looked like formatted text from a website or document, not something typed here — it's been
            bracketed in the field. Please rewrite it in your own words.
        </p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- LEFT: Case Details + Evidence --}}
        <div class="xl:col-span-2 space-y-5">

            {{-- Tabs — scenario/evidence/instructions text is fair game to quote in
                 an answer, so copies from here are marked as in-app, not external. --}}
            <div class="bg-surface  border border-edge  overflow-hidden" x-init="markInternalCopySource($el)">
                <div class="border-b border-edge flex">
                    @foreach(['scenario' => 'Scenario', 'evidence' => 'Evidence', 'instructions' => 'Instructions'] as $tab => $label)
                    <button @click="activeTab = '{{ $tab }}'"
                        :class="activeTab === '{{ $tab }}' ? 'border-b-2 border-blue text-blue bg-blue-soft' : 'text-fg-2 hover:text-fg'"
                        class="px-5 py-3 text-sm font-medium transition flex-1 text-center"
                        @click.once="logActivity('TAB_VIEWED', 'Viewed {{ $tab }} tab')">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>

                {{-- Scenario Tab --}}
                <div x-show="activeTab === 'scenario'" x-transition.opacity.duration.200ms class="p-5 space-y-4">
                    <div class="bg-red-soft border border-red  p-4">
                        <p class="text-xs font-semibold text-red uppercase tracking-wide mb-2">⚠️ Incident Report</p>
                        <p class="text-sm text-fg leading-relaxed">{{ $forensicCase->scenario }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-fg-2 uppercase tracking-wide mb-2">Learning Objectives</p>
                        <div class="text-sm text-fg space-y-1 leading-relaxed">
                            @foreach(explode("\n", $forensicCase->learning_objectives) as $obj)
                                @if(trim($obj))<p>{{ trim($obj) }}</p>@endif
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Evidence Tab --}}
                <div x-show="activeTab === 'evidence'" x-transition.opacity.duration.200ms class="p-0">
                    @php $evidence = $forensicCase->simulated_evidence ?? []; @endphp
                    @if(!empty($evidence))
                    <div class="p-4 bg-base border-b border-edge">
                        <p class="text-xs font-semibold text-fg-2">{{ $evidence['overview'] ?? 'Simulated evidence for this case.' }}</p>
                    </div>

                    {{-- Sub-tabs for evidence types --}}
                    {{-- Note: suspects (and their culprit flag) are deliberately excluded from
                         what's fed to the client-side terminal — that data must stay server-side
                         only (see the accuse() endpoint) so the answer can't be read via devtools. --}}
                    <div x-data="{ evidenceTab: 'audit_logs', ...terminalTool(@js(collect($evidence)->except('suspects')->all()), @js($forensicCase->timeline_events ?? [])) }">
                        <div class="flex border-b border-edge bg-base">
                            @if(!empty($evidence['audit_logs']))
                            <button @click="evidenceTab = 'audit_logs'; logActivity('EVIDENCE_OPENED', 'Opened audit logs')"
                                :class="evidenceTab === 'audit_logs' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-2'"
                                class="px-4 py-2 text-xs font-medium">Audit Logs ({{ count($evidence['audit_logs']) }})</button>
                            @endif
                            @if(!empty($evidence['database_records']))
                            <button @click="evidenceTab = 'db_records'; logActivity('EVIDENCE_OPENED', 'Opened database records')"
                                :class="evidenceTab === 'db_records' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-2'"
                                class="px-4 py-2 text-xs font-medium">DB Records</button>
                            @endif
                            @if(!empty($evidence['network_logs']))
                            <button @click="evidenceTab = 'network'; logActivity('EVIDENCE_OPENED', 'Opened network logs')"
                                :class="evidenceTab === 'network' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-2'"
                                class="px-4 py-2 text-xs font-medium">Network Logs</button>
                            @endif
                            @if(!empty($forensicCase->timeline_events))
                            <button @click="evidenceTab = 'timeline'; logActivity('EVIDENCE_OPENED', 'Opened incident timeline')"
                                :class="evidenceTab === 'timeline' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-3'"
                                class="px-4 py-2 text-xs font-medium">Timeline</button>
                            @endif
                            @if(!empty($evidence['system_info']))
                            <button @click="evidenceTab = 'system'; logActivity('EVIDENCE_OPENED', 'Opened system info')"
                                :class="evidenceTab === 'system' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-2'"
                                class="px-4 py-2 text-xs font-medium">System Info</button>
                            @endif
                            <button @click="evidenceTab = 'terminal'; logActivity('EVIDENCE_OPENED', 'Opened terminal'); $nextTick(() => $refs.termInput && $refs.termInput.focus())"
                                :class="evidenceTab === 'terminal' ? 'text-blue bg-surface border-b-2 border-blue' : 'text-fg-2'"
                                class="px-4 py-2 text-xs font-medium inline-flex items-center gap-1.5">
                                <i data-lucide="terminal" class="w-3 h-3"></i> Terminal
                            </button>
                        </div>

                        {{-- Audit Logs --}}
                        @if(!empty($evidence['audit_logs']))
                        <div x-show="evidenceTab === 'audit_logs'" x-transition.opacity.duration.200ms class="evidence-panel " style="max-height: 380px; overflow-y: auto;">
                            <div class="px-3 py-2 text-xs text-[#8891A5] border-b border-edge-3 font-mono flex gap-4">
                                <span class="w-40">TIMESTAMP</span><span class="w-24">USER</span><span class="w-28">ACTION</span><span>DETAILS</span>
                            </div>
                            @foreach($evidence['audit_logs'] as $log)
                            <div class="log-line flex gap-4">
                                <span class="w-40 text-[#6E9BFF]">{{ $log['timestamp'] }}</span>
                                <span class="w-24 text-white">{{ $log['user'] }}</span>
                                <span class="w-28 text-[#00c2ff]">{{ $log['action'] }}</span>
                                <span class="text-[#8891A5] flex-1">{{ $log['details'] }}</span>
                                @if(!empty($log['ip']))<span class="text-[#FF7A7A] text-xs">{{ $log['ip'] }}</span>@endif
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- DB Records --}}
                        @if(!empty($evidence['database_records']))
                        <div x-show="evidenceTab === 'db_records'" x-transition.opacity.duration.200ms class="p-4 overflow-x-auto">
                            <table class="w-full text-xs font-mono">
                                <thead class="bg-raised text-fg-3">
                                    <tr>
                                        @foreach(array_keys($evidence['database_records'][0] ?? []) as $col)
                                        <th class="text-left px-3 py-2">{{ strtoupper($col) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="bg-surface text-fg-3 divide-y divide-edge">
                                    @foreach($evidence['database_records'] as $record)
                                    <tr class="hover:bg-raised">
                                        @foreach($record as $val)
                                        <td class="px-3 py-2">{{ $val }}</td>
                                        @endforeach
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Network Logs --}}
                        @if(!empty($evidence['network_logs']))
                        <div x-show="evidenceTab === 'network'" x-transition.opacity.duration.200ms class="evidence-panel" style="max-height: 320px; overflow-y: auto;">
                            @foreach($evidence['network_logs'] as $log)
                            <div class="log-line">
                                <span class="text-[#6E9BFF]">{{ $log['timestamp'] ?? '' }}</span>
                                <span class="text-white ml-3">{{ $log['source_ip'] ?? '' }}</span>
                                <span class="text-[#8891A5] ml-3">{{ $log['event'] ?? '' }}</span>
                                @foreach(array_diff_key($log, array_flip(['timestamp','source_ip','event'])) as $k => $v)
                                <span class="text-[#FF7A7A] ml-2 text-xs">{{ $k }}: {{ $v }}</span>
                                @endforeach
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Incident Timeline --}}
                        @if(!empty($forensicCase->timeline_events))
                        <div x-show="evidenceTab === 'timeline'" x-transition.opacity.duration.200ms class="p-5">
                            <div class="relative pl-5 border-l border-edge space-y-4">
                                @foreach($forensicCase->timeline_events as $t)
                                <div class="relative">
                                    <span class="absolute -left-[23px] top-1.5 w-2 h-2 bg-blue"></span>
                                    <p class="font-mono text-[11px] text-blue">{{ $t['timestamp'] ?? '' }}</p>
                                    <p class="text-[13px] text-fg mt-0.5 leading-relaxed">{{ $t['event'] ?? '' }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- System Info --}}
                        @if(!empty($evidence['system_info']))
                        <div x-show="evidenceTab === 'system'" x-transition.opacity.duration.200ms class="p-4">
                            <div class="grid grid-cols-2 gap-3">
                                @foreach($evidence['system_info'] as $key => $val)
                                <div class="bg-base  p-3">
                                    <p class="text-xs text-fg-2 uppercase">{{ str_replace('_', ' ', $key) }}</p>
                                    <p class="font-mono text-sm font-semibold text-fg mt-0.5">{{ is_bool($val) ? ($val ? 'Yes' : 'No') : $val }}</p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Terminal --}}
                        <div x-show="evidenceTab === 'terminal'" x-transition.opacity.duration.200ms
                            class="evidence-panel flex flex-col" style="height: 380px;"
                            @click="$refs.termInput && $refs.termInput.focus()">
                            {{-- Note: this panel is deliberately fixed-dark in both site themes
                                 (like the rest of .evidence-panel), so every color here is a
                                 frozen hex value, never a theme token (fg/blue/success/etc. would
                                 go near-black-on-black once the light theme flips those tokens). --}}
                            <div class="flex-1 overflow-y-auto px-3 py-2" x-ref="termOutput">
                                <template x-for="(line, i) in termLines" :key="i">
                                    <div class="log-line !border-0" :class="line.type === 'cmd' ? 'text-[#00c2ff]' : (line.type === 'err' ? 'text-[#FF7A7A]' : 'text-[#C5CBD6]')">
                                        <span x-show="line.type === 'cmd'" class="text-[#3ddc84]">investigator@case-{{ $forensicCase->id }}</span><span x-show="line.type === 'cmd'" class="text-[#7C8798]">:~$&nbsp;</span><span x-text="line.text" style="white-space: pre-wrap;"></span>
                                    </div>
                                </template>
                            </div>
                            <div class="flex items-center gap-1.5 border-t border-[#16223a] px-3 py-2 flex-shrink-0">
                                <span class="text-[#3ddc84] font-mono text-[11.5px]">investigator@case-{{ $forensicCase->id }}</span><span class="text-[#7C8798] font-mono text-[11.5px]">:~$</span>
                                <input x-ref="termInput" x-model="termInput"
                                    @keydown.enter="termInput.trim() && logActivity('TERMINAL_COMMAND', 'Ran: ' + termInput.trim()); submitCommand()"
                                    @keydown.up.prevent="historyUp()" @keydown.down.prevent="historyDown()"
                                    type="text" autocomplete="off" spellcheck="false"
                                    class="flex-1 bg-transparent border-0 outline-none font-mono text-[11.5px] text-[#eaf2ff]"
                                    placeholder="type 'help' to get started">
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="p-8 text-center text-fg-3 text-sm">No simulated evidence configured for this case.</div>
                    @endif
                </div>

                {{-- Instructions Tab --}}
                <div x-show="activeTab === 'instructions'" x-transition.opacity.duration.200ms class="p-5">
                    <div class="prose prose-sm max-w-none text-fg">
                        @foreach(explode("\n", $forensicCase->investigation_instructions) as $line)
                            @if(trim($line))
                                <p class="mb-2 leading-relaxed">{{ trim($line) }}</p>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Suspect Lineup — only shows when the lecturer configured suspects
                 for this case. Names/roles are rendered server-side; the correct
                 answer never reaches the browser, so it can't be read from
                 devtools/view-source — the accusation is checked by the server. --}}
            @php $suspects = $forensicCase->simulated_evidence['suspects'] ?? []; @endphp
            @if(!empty($suspects))
            <div class="bg-surface border border-edge"
                x-data="{
                    picked: null,
                    result: null,
                    solved: {{ $enrollment->accusation_correct ? 'true' : 'false' }},
                    solvedName: {{ $enrollment->accusation_correct ? json_encode($enrollment->accused_suspect) : 'null' }},
                    async accuse(name) {
                        if (this.solved) return;
                        this.picked = name;
                        this.result = null;
                        try {
                            const res = await fetch('{{ route('student.case.accuse', $forensicCase) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                },
                                body: JSON.stringify({ name }),
                            });
                            const data = await res.json();
                            this.result = data.correct ? 'correct' : 'wrong';
                            if (data.correct) { this.solved = true; this.solvedName = data.culprit; }
                        } catch (e) { this.result = 'error'; }
                    },
                }">
                <div class="px-5 py-4 border-b border-edge flex items-center justify-between">
                    <h3 class="font-semibold text-fg inline-flex items-center gap-2">
                        <i data-lucide="user-search" class="w-4 h-4 text-blue"></i> Name the Suspect
                    </h3>
                    <span class="text-xs text-fg-3" x-show="!solved">Pick who you think is responsible</span>
                    <span class="text-xs text-success font-semibold" x-show="solved" x-cloak>Case solved</span>
                </div>
                <div class="p-5">
                    <div x-show="solved" x-cloak class="bg-success-soft border border-success px-4 py-3 mb-4 flex items-start gap-2.5">
                        <i data-lucide="circle-check-big" class="w-4 h-4 text-success flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-fg">You correctly named <strong x-text="solvedName"></strong> as the responsible party. Use this in your report's findings.</p>
                    </div>
                    <div x-show="result === 'wrong'" x-cloak x-transition class="bg-red-soft border border-red px-4 py-3 mb-4 flex items-start gap-2.5">
                        <i data-lucide="circle-x" class="w-4 h-4 text-red flex-shrink-0 mt-0.5"></i>
                        <p class="text-sm text-fg">Not <strong x-text="picked"></strong> — look again at who had access, motive, and opportunity in the evidence.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        @foreach($suspects as $s)
                        <button type="button" @click="accuse('{{ addslashes($s['name']) }}')"
                            :disabled="solved"
                            :class="{
                                'border-success bg-success-soft': solved && solvedName === '{{ addslashes($s['name']) }}',
                                'border-red': !solved && result === 'wrong' && picked === '{{ addslashes($s['name']) }}',
                                'opacity-50 cursor-not-allowed': solved && solvedName !== '{{ addslashes($s['name']) }}',
                            }"
                            class="text-left border border-edge p-3.5 hover:border-blue hover:bg-raised disabled:pointer-events-none transition rounded-lg">
                            <p class="text-sm font-semibold text-fg">{{ $s['name'] }}</p>
                            <p class="text-xs text-fg-3 mt-0.5">{{ $s['role'] }}</p>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Investigation Questions --}}
            <div class="bg-surface  border border-edge ">
                <div class="px-5 py-4 border-b border-edge flex items-center justify-between">
                    <h3 class="font-semibold text-fg">Investigation Questions</h3>
                    <span class="text-xs text-fg-3">Answer all questions to complete the investigation</span>
                </div>
                <div class="p-5 space-y-5">
                    @foreach($questions as $i => $question)
                    <div class="border border-edge  p-4">
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="w-7 h-7 rounded-full bg-blue-soft text-blue text-xs font-bold flex items-center justify-center">{{ $i + 1 }}</span>
                                <p class="text-sm font-medium text-fg">{{ $question->question }}</p>
                            </div>
                            <span class="text-xs text-fg-3 bg-base px-2 py-1  ml-2 flex-shrink-0">{{ $question->marks }} marks</span>
                        </div>
                        <textarea
                            class="w-full border border-edge bg-input p-3 text-sm text-fg focus:outline-none focus:border-blue resize-none"
                            rows="4"
                            placeholder="Write your investigation findings here..."
                            @blur="saveAnswer({{ $question->id }}, $el.value)"
                            @paste="handlePaste($event, {{ $question->id }})"
                            x-data
                            x-init="$el.value = `{{ addslashes($answers[$question->id] ?? '') }}`"
                        ></textarea>
                        <p class="text-xs text-fg-3 mt-1">Auto-saved when you click away</p>
                    </div>
                    @endforeach
                </div>
                @if($forensicCase->question_pdf_path)
                <div class="px-5 pb-3">
                    <div class="bg-blue-soft border border-edge px-4 py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[12.5px] font-medium text-fg">Question sheet: {{ $forensicCase->question_pdf_name }}</p>
                            <p class="text-[11.5px] text-fg-3 mt-0.5">Download and attach your answers with the report.</p>
                        </div>
                        <a href="{{ route('student.case.questions', $forensicCase) }}" class="btn-ghost px-3 py-2 flex-shrink-0">Download</a>
                    </div>
                </div>
                @endif

                <div class="px-5 pb-5">
                    <a href="{{ route('student.report.show', $forensicCase) }}"
                        class="inline-flex items-center gap-2 bg-blue hover:bg-blue-deep text-base px-6 py-2.5  text-sm font-semibold transition">
                        Proceed to Write Report
                    </a>
                </div>
            </div>
        </div>

        {{-- RIGHT: Activity Log Sidebar --}}
        <div class="space-y-5">
            {{-- Case Info --}}
            <div class="bg-surface  border border-edge  p-4">
                <h4 class="text-xs font-semibold text-fg-2 uppercase tracking-wide mb-3">Case Info</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-fg-2">Type</span>
                        <span class="font-medium text-fg text-right text-xs">{{ $forensicCase->incident_label }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-fg-2">Difficulty</span>
                        <span class="diff diff-{{ $forensicCase->difficulty }}">{{ ucfirst($forensicCase->difficulty) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-fg-2">Lecturer</span>
                        <span class="font-medium text-fg text-xs">{{ $forensicCase->lecturer->name }}</span>
                    </div>
                </div>
            </div>

            {{-- Activity Log --}}
            <div class="bg-surface  border border-edge  overflow-hidden">
                <div class="px-4 py-3 border-b border-edge flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue animate-pulse-dot"></span>
                    <h4 class="text-xs font-semibold text-fg-2 uppercase tracking-wide">Your Activity Log</h4>
                </div>
                <div class="divide-y divide-edge max-h-80 overflow-y-auto" id="activity-log">
                    @forelse($activityLogs as $log)
                    <div class="px-4 py-2.5">
                        <div class="flex items-start gap-2">
                            <span class="text-xs text-fg-3 font-mono w-14 flex-shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
                            <div>
                                <p class="text-xs font-medium text-fg">{{ $log->action }}</p>
                                @if($log->description)<p class="text-xs text-fg-3 mt-0.5">{{ $log->description }}</p>@endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="px-4 py-6 text-center text-xs text-fg-3">Activity will appear here as you investigate.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// A constrained, fake command-line interpreter that runs entirely in the
// browser against the case's own simulated evidence — no real shell, no
// server round-trip per command. It exists to make digging through evidence
// feel more like an HTB/THM-style investigation (typing commands to pull
// out what you need) without needing real per-student sandboxes.
function terminalTool(evidence, timelineEvents) {
    const files = {};
    if (evidence.audit_logs?.length) {
        files['audit.log'] = evidence.audit_logs.map(l =>
            `[${l.timestamp || ''}] user=${l.user || ''} action=${l.action || ''}` +
            (l.ip ? ` ip=${l.ip}` : '') + ` :: ${l.details || ''}`
        );
    }
    if (evidence.database_records?.length) {
        const cols = Object.keys(evidence.database_records[0]);
        files['database.csv'] = [cols.join(',')].concat(
            evidence.database_records.map(r => cols.map(c => r[c]).join(','))
        );
    }
    if (evidence.network_logs?.length) {
        files['network.log'] = evidence.network_logs.map(l => {
            const known = ['timestamp', 'source_ip', 'event'];
            const extra = Object.keys(l).filter(k => !known.includes(k)).map(k => `${k}=${l[k]}`).join(' ');
            return `[${l.timestamp || ''}] ${l.source_ip || ''} ${l.event || ''}` + (extra ? ` ${extra}` : '');
        });
    }
    if (evidence.system_info && Object.keys(evidence.system_info).length) {
        files['system.info'] = Object.entries(evidence.system_info).map(([k, v]) =>
            `${k}: ${typeof v === 'boolean' ? (v ? 'yes' : 'no') : v}`
        );
    }
    if (timelineEvents?.length) {
        files['timeline.log'] = timelineEvents.map(t => `[${t.timestamp || ''}] ${t.event || ''}`);
    }

    return {
        termLines: [
            { type: 'out', text: 'AI-ForensicEdu investigation shell — evidence for this case is mounted read-only.' },
            { type: 'out', text: "Type 'help' to see available commands." },
        ],
        termInput: '',
        termHistory: [],
        termHistoryIdx: -1,

        scrollTerm() {
            this.$nextTick(() => {
                const el = this.$refs.termOutput;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        historyUp() {
            if (!this.termHistory.length) return;
            this.termHistoryIdx = Math.max(0, this.termHistoryIdx - 1);
            this.termInput = this.termHistory[this.termHistoryIdx] ?? '';
        },
        historyDown() {
            if (!this.termHistory.length) return;
            this.termHistoryIdx = Math.min(this.termHistory.length, this.termHistoryIdx + 1);
            this.termInput = this.termHistory[this.termHistoryIdx] ?? '';
        },

        submitCommand() {
            const raw = this.termInput.trim();
            this.termInput = '';
            this.termLines.push({ type: 'cmd', text: raw });
            if (raw) {
                this.termHistory.push(raw);
                this.termHistoryIdx = this.termHistory.length;
            }
            this.runCommand(raw, files);
            this.scrollTerm();
        },

        runCommand(raw, files) {
            const push = (text, type = 'out') => this.termLines.push({ type, text });
            if (!raw) return;
            const parts = raw.split(/\s+/);
            const cmd = parts[0].toLowerCase();
            const args = parts.slice(1);

            switch (cmd) {
                case 'help':
                    push('Available commands:');
                    push('  ls                    list evidence files mounted for this case');
                    push('  cat <file>            print a file in full');
                    push('  grep <pattern> <file>  print lines from <file> containing <pattern>');
                    push('  find <keyword>        search every mounted file for <keyword>');
                    push('  whoami                show the identity you are investigating as');
                    push('  clear                 clear the screen');
                    break;

                case 'ls':
                    if (Object.keys(files).length === 0) { push('ls: no evidence files mounted for this case'); break; }
                    Object.entries(files).forEach(([name, lines]) => {
                        push(`-rw-r--r--  investigator  forensics  ${String(lines.length).padStart(4, ' ')}  ${name}`);
                    });
                    break;

                case 'cat': {
                    const name = args[0];
                    if (!name) { push('usage: cat <file>', 'err'); break; }
                    if (!files[name]) { push(`cat: ${name}: No such file`, 'err'); break; }
                    files[name].forEach(l => push(l));
                    break;
                }

                case 'grep': {
                    if (args.length < 2) { push('usage: grep <pattern> <file>', 'err'); break; }
                    const pattern = args[0];
                    const name = args[1];
                    if (!files[name]) { push(`grep: ${name}: No such file`, 'err'); break; }
                    const hits = files[name].filter(l => l.toLowerCase().includes(pattern.toLowerCase()));
                    if (!hits.length) { push(`grep: no matches for "${pattern}" in ${name}`); break; }
                    hits.forEach(l => push(l));
                    break;
                }

                case 'find': {
                    const keyword = args.join(' ');
                    if (!keyword) { push('usage: find <keyword>', 'err'); break; }
                    let any = false;
                    Object.entries(files).forEach(([name, lines]) => {
                        lines.filter(l => l.toLowerCase().includes(keyword.toLowerCase()))
                            .forEach(l => { push(`${name}: ${l}`); any = true; });
                    });
                    if (!any) push(`find: nothing matching "${keyword}"`);
                    break;
                }

                case 'whoami':
                    push('{{ auth()->user()->name }} ({{ auth()->user()->role }})');
                    break;

                case 'clear':
                    this.termLines = [];
                    return;

                default:
                    push(`bash: ${cmd}: command not found`, 'err');
            }
        },
    };
}

function caseInvestigation() {
    return {
        activeTab: 'scenario',
        progress: {{ $enrollment->progress_percent }},
        saving: {},
        justSaved: false,
        externalPasteWarning: false,
        externalPasteFlags: {},

        init() {
            this.logActivity('CASE_VIEWED', 'Started investigation session');
        },

        handlePaste(e, questionId) {
            const { text, isRich } = extractPasteInfo(e);
            if (text.length === 0) return;

            if (isRich) {
                e.preventDefault();
                insertWithExternalMarker(e.target, text);
                this.externalPasteWarning = true;
                setTimeout(() => this.externalPasteWarning = false, 7000);
                this.externalPasteFlags[questionId] = true;
                this.logActivity('EXTERNAL_PASTE_DETECTED', `Pasted formatted text (${text.length} chars) into an answer — flagged in place`);
            }
        },

        async saveAnswer(questionId, answer) {
            if (!answer.trim() || this.saving[questionId]) return;
            this.saving[questionId] = true;
            try {
                const res = await fetch('{{ route('student.case.save-answer', $forensicCase) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ question_id: questionId, answer, external_paste: !!this.externalPasteFlags[questionId] }),
                });
                const data = await res.json();
                if (data.success) {
                    const increased = data.progress > this.progress;
                    this.progress = data.progress;
                    if (increased) {
                        this.justSaved = true;
                        setTimeout(() => this.justSaved = false, 1800);
                    }
                }
            } catch(e) { console.error(e); }
            this.saving[questionId] = false;
        },

        async logActivity(action, description) {
            try {
                await fetch('{{ route('student.case.log', $forensicCase) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ action, description }),
                });
                // Append to log
                const log = document.getElementById('activity-log');
                if(log) {
                    const now = new Date().toTimeString().slice(0,8);
                    const row = `<div class="px-4 py-2.5 border-b border-edge">
                        <div class="flex items-start gap-2">
                            <span class="text-xs text-fg-3 font-mono w-14 flex-shrink-0">${now}</span>
                            <div><p class="text-xs font-medium text-fg">${action}</p>
                            <p class="text-xs text-fg-3 mt-0.5">${description}</p></div>
                        </div></div>`;
                    log.insertAdjacentHTML('afterbegin', row);
                }
            } catch(e) {}
        }
    }
}
</script>
@endpush
