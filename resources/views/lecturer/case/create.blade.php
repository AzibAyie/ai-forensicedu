@extends('layouts.app')
@section('title', 'Create Case')
@section('page-title', 'Create Forensic Case')
@section('page-subtitle', 'Build a new case scenario for your students')

@section('content')
<div class="py-4 max-w-4xl" x-data="caseCreator()">

    {{-- AI Generator Panel --}}
    <div class="bg-black  p-5 mb-5 text-white rounded-2xl">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <span class="text-xl">🤖</span>
                <div>
                    <h3 class="font-semibold">AI Case Generator</h3>
                    <p class="text-white/70 text-xs">Let AI generate a complete case scenario for you</p>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-3 gap-3 mb-3">
            <div>
                <label class="text-xs text-white/70 mb-1 block">Incident Type</label>
                <select x-model="aiType" class="w-full bg-white/10 border border-white/20  px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-white/50 rounded-lg">
                    <option value="unauthorized_modification">Unauthorized Data Modification</option>
                    <option value="brute_force">Brute Force Attack</option>
                    <option value="mass_deletion">Mass Data Deletion</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-white/70 mb-1 block">Difficulty</label>
                <select x-model="aiDifficulty" class="w-full bg-white/10 border border-white/20  px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-white/50 rounded-lg">
                    <option value="beginner">Beginner</option>
                    <option value="intermediate">Intermediate</option>
                    <option value="advanced">Advanced</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-white/70 mb-1 block">Context (optional)</label>
                <input type="text" x-model="aiContext" placeholder="e.g. banking system, hospital"
                    class="w-full bg-white/10 border border-white/20  px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-white/50 placeholder-white/40 rounded-lg">
            </div>
        </div>
        <button @click="generateAI()" :disabled="generating"
            class="bg-surface text-blue font-semibold px-5 py-2  text-sm hover:bg-blue-soft transition disabled:opacity-50 rounded-lg">
            <span x-show="!generating">✨ Generate with AI</span>
            <span x-show="generating">⏳ Generating...</span>
        </button>
        <div x-show="aiError" class="mt-2 text-red-dim text-xs" x-text="aiError"></div>
        <div x-show="aiSuccess" class="mt-2 text-fg-2 text-xs">✅ Case generated! Review and edit the fields below before saving.</div>
    </div>

    {{-- Case Form --}}
    <form method="POST" action="{{ route('lecturer.case.store') }}" class="space-y-5" enctype="multipart/form-data">
        @csrf

        {{-- Basic Info --}}
        <div class="bg-surface  border border-edge  p-6">
            <h3 class="font-semibold text-fg mb-4 pb-2 border-b">Case Information</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Case Title *</label>
                    <input type="text" name="title" :value="form.title" x-model="form.title" required
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue"
                        placeholder="e.g. The FictiBank Payroll Fraud Incident">
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-fg-2 mb-1">Incident Type *</label>
                        <select name="incident_type" x-model="form.incident_type" required
                            class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue">
                            <option value="">Select type...</option>
                            <option value="unauthorized_modification">Unauthorized Data Modification</option>
                            <option value="brute_force">Brute Force Login Attack</option>
                            <option value="mass_deletion">Mass Data Deletion</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-fg-2 mb-1">Difficulty *</label>
                        <select name="difficulty" x-model="form.difficulty" required
                            class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue">
                            <option value="">Select...</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-fg-2 mb-1">Duration (minutes)</label>
                        <input type="number" name="expected_duration" x-model="form.expected_duration" value="60" min="15" max="300"
                            class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Short Description *</label>
                    <input type="text" name="description" x-model="form.description" required
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue"
                        placeholder="Brief overview of the case...">
                </div>
            </div>
        </div>

        {{-- Scenario --}}
        <div class="bg-surface  border border-edge  p-6">
            <h3 class="font-semibold text-fg mb-4 pb-2 border-b">Case Scenario & Instructions</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Full Scenario *</label>
                    <textarea name="scenario" x-model="form.scenario" required rows="5"
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue resize-none"
                        placeholder="Describe the incident scenario in detail..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Learning Objectives *</label>
                    <textarea name="learning_objectives" x-model="form.learning_objectives" required rows="4"
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue resize-none"
                        placeholder="1. Students will be able to...&#10;2. Students will identify..."></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Investigation Instructions *</label>
                    <textarea name="investigation_instructions" x-model="form.investigation_instructions" required rows="5"
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue resize-none"
                        placeholder="Step 1: Examine the audit logs...&#10;Step 2: Compare database records..."></textarea>
                </div>
            </div>
        </div>

        {{-- Questions --}}
        <div class="bg-surface  border border-edge  p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h3 class="font-semibold text-fg">Investigation Questions</h3>
                <button type="button" @click="addQuestion()" class="text-sm text-blue font-medium hover:underline">+ Add Question</button>
            </div>
            <div class="space-y-3" id="questions-container">
                <template x-for="(q, i) in form.questions" :key="i">
                    <div class="border border-edge  p-4">
                        <div class="flex items-start gap-3">
                            <span class="w-6 h-6 rounded-full bg-blue-soft text-blue text-xs font-bold flex items-center justify-center flex-shrink-0 mt-1" x-text="i+1"></span>
                            <div class="flex-1">
                                <input type="text" :name="`questions[${i}][question]`" x-model="q.question" required
                                    class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue mb-2"
                                    placeholder="Investigation question...">
                                <div class="flex items-center gap-3">
                                    <label class="text-xs text-fg-3">Marks:</label>
                                    <input type="number" :name="`questions[${i}][marks]`" x-model="q.marks" min="1" max="100" required
                                        class="w-20 border border-edge  px-2 py-1 text-sm focus:outline-none focus:border-blue">
                                    <button type="button" @click="removeQuestion(i)" class="text-fg-3 hover:text-red text-xs ml-auto" x-show="form.questions.length > 1">Remove</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
            <p class="text-xs text-fg-3 mt-3">
                Total marks: <strong x-text="form.questions.reduce((s,q) => s + parseInt(q.marks||0), 0)"></strong>
            </p>
        </div>

        {{-- Timeline & audit log authoring --}}
        <div class="bg-surface border border-edge p-6">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-edge">
                <div>
                    <h3 class="font-display text-[14px] font-semibold text-fg">Timeline &amp; simulated events</h3>
                    <p class="text-[12px] text-fg-3 mt-0.5">Optional. Adds your own entries on top of the generated evidence for this incident type.</p>
                </div>
            </div>

            <p class="eyebrow mb-2">Incident timeline</p>
            <div class="space-y-2 mb-5">
                <template x-for="(t, i) in timeline" :key="'t'+i">
                    <div class="flex gap-2">
                        <input type="text" :name="`timeline_events[${i}][timestamp]`" x-model="t.timestamp"
                            placeholder="2024-03-15 02:14" class="fld font-mono" style="max-width:190px">
                        <input type="text" :name="`timeline_events[${i}][event]`" x-model="t.event"
                            placeholder="Attacker authenticated after 47 attempts" class="fld">
                        <button type="button" @click="timeline.splice(i,1)" class="btn-danger px-3">×</button>
                    </div>
                </template>
            </div>
            <button type="button" @click="timeline.push({timestamp:'',event:''})" class="btn-ghost px-3 py-2 mb-6">+ Timeline entry</button>

            <p class="eyebrow mb-2">Additional audit log lines</p>
            <div class="space-y-2 mb-4">
                <template x-for="(l, i) in customLogs" :key="'l'+i">
                    <div class="grid grid-cols-12 gap-2">
                        <input type="text" :name="`custom_logs[${i}][timestamp]`" x-model="l.timestamp" placeholder="timestamp" class="fld font-mono col-span-3">
                        <input type="text" :name="`custom_logs[${i}][user]`" x-model="l.user" placeholder="user" class="fld font-mono col-span-2">
                        <input type="text" :name="`custom_logs[${i}][action]`" x-model="l.action" placeholder="ACTION" class="fld font-mono col-span-2">
                        <input type="text" :name="`custom_logs[${i}][ip]`" x-model="l.ip" placeholder="IP" class="fld font-mono col-span-2">
                        <input type="text" :name="`custom_logs[${i}][details]`" x-model="l.details" placeholder="details" class="fld col-span-2">
                        <button type="button" @click="customLogs.splice(i,1)" class="btn-danger px-2 col-span-1">×</button>
                    </div>
                </template>
            </div>
            <button type="button" @click="customLogs.push({timestamp:'',user:'',action:'',ip:'',details:''})" class="btn-ghost px-3 py-2">+ Log line</button>
        </div>

        {{-- Question sheet --}}
        <div class="bg-surface border border-edge p-6">
            <h3 class="font-display text-[14px] font-semibold text-fg mb-1 pb-2 border-b border-edge">Question sheet (PDF)</h3>
            <p class="text-[12px] text-fg-3 mb-3 mt-3">Optional. Students download this and upload their completed answers with the report.</p>
            <input type="file" name="question_pdf" accept="application/pdf"
                class="fld file:mr-3 file:border-0 file:bg-blue file:text-white file:px-3 file:py-1.5 file:text-[12px] file:font-semibold file:cursor-pointer">
        </div>

        {{-- Scheduling --}}
        <div class="bg-surface border border-edge p-6">
            <h3 class="font-display text-[14px] font-semibold text-fg mb-4 pb-2 border-b border-edge">Session window</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="eyebrow block mb-1.5">Opens at</label>
                    <input type="datetime-local" name="publish_at" class="fld">
                    <p class="text-[11px] text-fg-3 mt-1">Leave blank to open as soon as it is published.</p>
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Closes at</label>
                    <input type="datetime-local" name="close_at" class="fld">
                    <p class="text-[11px] text-fg-3 mt-1">Leave blank for no deadline.</p>
                </div>
            </div>
        </div>

        {{-- Security --}}
        <div class="bg-surface  border border-edge  p-6">
            <h3 class="font-semibold text-fg mb-4 pb-2 border-b">Access Control</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-fg-2 mb-1">Case Password (optional)</label>
                    <input type="text" name="password" placeholder="Leave empty for open access"
                        class="w-full border border-edge  px-3 py-2 text-sm focus:outline-none focus:border-blue">
                    <p class="text-xs text-fg-3 mt-1">Students must enter this to unlock the case.</p>
                </div>
                <div class="flex items-center gap-6 mt-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="publish" class="rounded text-blue" checked>
                        <span class="text-sm text-fg">Publish immediately</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex gap-3 justify-end">
            <a href="{{ route('lecturer.dashboard') }}" class="border border-white/30 text-white/80 px-5 py-2  text-sm hover:bg-white hover:text-fg transition rounded-lg">Cancel</a>
            <button type="submit" class="bg-black hover:bg-blue text-white px-6 py-2  text-sm font-semibold transition rounded-lg">
                ✅ Create Case
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function caseCreator() {
    return {
        generating: false,
        aiType: 'unauthorized_modification',
        aiDifficulty: 'intermediate',
        aiContext: '',
        aiError: '',
        aiSuccess: false,
        timeline: [{timestamp:'',event:''}],
        customLogs: [],
        form: {
            title: '',
            incident_type: '',
            difficulty: '',
            expected_duration: 60,
            description: '',
            scenario: '',
            learning_objectives: '',
            investigation_instructions: '',
            questions: [
                { question: '', marks: 20 },
                { question: '', marks: 20 },
                { question: '', marks: 20 },
                { question: '', marks: 20 },
                { question: '', marks: 20 },
            ]
        },

        addQuestion() {
            this.form.questions.push({ question: '', marks: 10 });
        },

        removeQuestion(i) {
            this.form.questions.splice(i, 1);
        },

        async generateAI() {
            this.generating = true;
            this.aiError = '';
            this.aiSuccess = false;
            try {
                const res = await fetch('{{ route('lecturer.case.generate-ai') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        incident_type: this.aiType,
                        difficulty: this.aiDifficulty,
                        context: this.aiContext,
                    }),
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const d = data.data;
                    this.form.title = d.title || '';
                    this.form.incident_type = this.aiType;
                    this.form.difficulty = this.aiDifficulty;
                    this.form.description = d.description || '';
                    this.form.scenario = d.scenario || '';
                    this.form.learning_objectives = d.learning_objectives || '';
                    this.form.investigation_instructions = d.investigation_instructions || '';
                    if (d.questions?.length) {
                        this.form.questions = d.questions.map(q => ({ question: q.question, marks: q.marks }));
                    }
                    this.aiSuccess = true;
                } else {
                    this.aiError = data.message || 'AI generation failed. Please fill in manually.';
                }
            } catch(e) {
                this.aiError = 'Connection error. Please fill in manually.';
            }
            this.generating = false;
        }
    }
}
</script>
@endpush
