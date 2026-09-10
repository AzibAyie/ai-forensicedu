<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'AI-ForensicEdu')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-icon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Digital-forensics dashboard palette — dark navy surfaces
                        // with cyan highlights (see design.md for the full spec).
                        base:     '#08111f',   // page background
                        sidebar:  '#0b1728',   // sidebar background
                        surface:  '#111d2e',   // cards, panels — the "boxes"
                        raised:   '#172a43',   // hover / nested panels
                        elevated: '#15243a',   // elevated panels
                        input:    '#0d1a2b',   // form field background
                        edge:     { DEFAULT: '#263a55', hover: '#365170' },
                        // Text — light on dark, varied by opacity for hierarchy
                        fg:       { DEFAULT: '#eaf2ff', 2: 'rgba(234,242,255,0.72)', 3: 'rgba(234,242,255,0.52)' },
                        heading:  '#f8fafc',
                        // Brand and actions — cyan primary; purple reserved for AI
                        blue:     { DEFAULT: '#00c2ff', hover: '#38d2ff', deep: '#0891b2', soft: 'rgba(0,194,255,0.14)' },
                        secondary:'#2563eb',
                        ai:       { DEFAULT: '#8b5cf6', hover: '#a78bfa', soft: 'rgba(139,92,246,0.14)' },
                        // Status
                        success:  { DEFAULT: '#22c55e', soft: 'rgba(34,197,94,0.14)' },
                        warning:  { DEFAULT: '#f59e0b', soft: 'rgba(245,158,11,0.14)' },
                        red:      { DEFAULT: '#ef4444', deep: '#b91c1c', soft: 'rgba(239,68,68,0.14)' },
                        info:     { DEFAULT: '#3b82f6', soft: 'rgba(59,130,246,0.14)' },
                    },
                    fontFamily: {
                        display: ['"Inter"', 'Arial', 'sans-serif'],
                        sans:    ['"Inter"', 'Arial', 'sans-serif'],
                        mono:    ['"IBM Plex Mono"', 'monospace'],
                    },
                    boxShadow: {
                        glow:     '0 0 0 1px rgba(0,194,255,0.5), 0 10px 30px -10px rgba(0,194,255,0.45)',
                        'glow-sm':'0 0 0 3px rgba(0,194,255,0.16)',
                        'glow-red':'0 0 0 1px rgba(239,68,68,0.5), 0 10px 30px -10px rgba(239,68,68,0.45)',
                    },
                }
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@0.462.0/dist/umd/lucide.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #08111f; color: #eaf2ff;
            background-image:
                radial-gradient(circle at 8% 0%, rgba(21,36,58,0.9), transparent 55%),
                radial-gradient(circle at 100% 100%, rgba(11,23,40,0.9), transparent 55%);
            background-attachment: fixed;
        }
        h1,h2,h3,h4 { font-family: 'Inter', Arial, sans-serif; font-weight: 700; letter-spacing: -0.01em; color: #f8fafc; }

        .eyebrow {
            font-family: 'Inter', Arial, sans-serif; font-size: 12px; font-weight: 600;
            letter-spacing: 0.04em; text-transform: uppercase; color: rgba(234,242,255,0.52);
        }

        .seal {
            font-family: 'Inter', Arial, sans-serif; font-size: 11px; font-weight: 600;
            letter-spacing: 0.02em;
            padding: 4px 10px; border: 1px solid currentColor; display: inline-flex; align-items: center; gap: 6px; line-height: 1;
            border-radius: 999px;
        }
        .seal-open   { color: #00c2ff; background: rgba(0,194,255,0.14); }
        .seal-active { color: #08111f; background: #00c2ff; border-color: #00c2ff; }
        .seal-graded { color: #22c55e; background: rgba(34,197,94,0.14); }
        .seal-draft  { color: rgba(234,242,255,0.55); background: rgba(234,242,255,0.08); }
        .seal-alert  { color: #ef4444; background: rgba(239,68,68,0.14); }

        .diff { font-family: 'Inter', Arial, sans-serif; font-size: 11px; font-weight: 600;
                letter-spacing: 0.02em; text-transform: uppercase; }
        .diff-beginner     { color: #00c2ff; }
        .diff-intermediate { color: #f59e0b; }
        .diff-advanced     { color: #ef4444; }

        /* Cards — rounded corners on the dark card surface.
           Tables are excluded: their sticky/frozen headers depend on the card being
           a plain overflow:visible box, and rounding would clip square header
           corners without it. */
        .bg-surface.border:not(:has(table)) {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }

        /* Terminal */
        .evidence-panel { background: #05080C; color: #C5CBD6; border-radius: 10px; }
        .log-line { font-family: 'IBM Plex Mono', monospace; font-size: 11.5px;
                    line-height: 1.9; border-bottom: 1px solid #16223a; padding: 2px 12px; }
        .log-line:hover { background: rgba(0,194,255,0.08); }

        /* Sidebar nav */
        .nav-link {
            display: flex; align-items: center; gap: 11px;
            min-height: 44px; padding: 0 14px; margin: 0 10px; font-size: 13.5px; font-weight: 500;
            color: rgba(234,242,255,0.62); border-left: 3px solid transparent; transition: all 200ms ease;
            border-radius: 8px;
        }
        .nav-link svg { width: 18px; height: 18px; flex-shrink: 0; }
        .nav-link:hover { color: #f8fafc; background: rgba(0,194,255,0.10); border-left-color: #00c2ff; transform: translateX(2px); }
        .nav-link.active { color: #00c2ff; background: rgba(0,194,255,0.14); border-left-color: #00c2ff; font-weight: 600;
            box-shadow: inset 0 0 18px rgba(0,194,255,0.08); }

        /* Interactive surfaces — cards that feel clickable, HTB-style */
        .card-interactive {
            border-radius: 12px;
            transition: transform 160ms ease, border-color 160ms ease, box-shadow 160ms ease;
        }
        .card-interactive:hover {
            transform: translateY(-2px);
            border-color: #00c2ff;
            box-shadow: 0 0 0 1px rgba(0,194,255,0.35), 0 14px 30px -12px rgba(0,194,255,0.35);
            z-index: 1; position: relative;
        }

        @keyframes glow-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(0,194,255,0.45); }
            50%      { box-shadow: 0 0 0 7px rgba(0,194,255,0); }
        }
        .flash-once { animation: glow-pulse 900ms ease-out 2; }

        @keyframes rise-in {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .rise-in { animation: rise-in 260ms ease-out both; }

        /* Form controls */
        .fld {
            width: 100%; background: #0d1a2b; border: 1px solid #263a55; color: #eaf2ff;
            padding: 10px 14px; font-size: 13.5px; min-height: 44px; transition: border-color 150ms ease, box-shadow 150ms ease;
            border-radius: 8px;
        }
        .fld:hover { border-color: #365170; }
        .fld:focus { outline: none; border-color: #00c2ff; box-shadow: 0 0 0 3px rgba(0,194,255,0.14); }
        .fld::placeholder { color: rgba(234,242,255,0.4); }
        select.fld option { background: #0d1a2b; color: #eaf2ff; }

        /* Raw (non-.fld) text inputs/selects/textareas used in a few older forms —
           round them too so every field in the app looks consistent. */
        input:not([type=checkbox]):not([type=radio]):not([type=file]), select, textarea {
            border-radius: 8px;
        }

        /* Initial/avatar chips (e.g. "AM", "FE") — the recurring
           border+border-edge+centered-flex square used across the app. */
        .border.border-edge.flex.items-center.justify-center,
        .border.border-blue.flex.items-center.justify-center {
            border-radius: 9999px;
        }

        .btn-primary {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 42px; padding: 10px 18px;
            background: #00c2ff; color: #08111f; font-weight: 600; border-radius: 8px;
            transition: background 150ms ease, box-shadow 150ms ease, transform 150ms ease;
        }
        .btn-primary:hover { background: #38d2ff; box-shadow: 0 0 16px rgba(0,194,255,0.35); transform: translateY(-2px); }

        .btn-ai {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            min-height: 42px; padding: 10px 18px;
            background: #8b5cf6; color: #fff; font-weight: 600; border-radius: 8px;
            transition: background 150ms ease, box-shadow 150ms ease, transform 150ms ease;
        }
        .btn-ai:hover { background: #a78bfa; box-shadow: 0 0 16px rgba(139,92,246,0.35); transform: translateY(-2px); }

        .btn-ghost {
            border: 1px solid #263a55; color: rgba(234,242,255,0.75); border-radius: 8px;
            font-family: 'Inter', Arial, sans-serif; font-size: 13px; font-weight: 600;
            padding: 10px 16px; transition: all 150ms ease;
        }
        .btn-ghost:hover { border-color: #00c2ff; color: #f8fafc; background: rgba(0,194,255,0.08); transform: translateY(-1px); }
        .btn-danger { border: 1px solid #ef4444; color: #ef4444; border-radius: 8px;
            font-family: 'Inter', Arial, sans-serif; font-size: 13px; font-weight: 600;
            padding: 10px 16px; transition: all 150ms ease; }
        .btn-danger:hover { background: #ef4444; color: #fff; box-shadow: 0 0 14px rgba(239,68,68,0.3); }

        .progress-bar { border-radius: 999px; transition: width 600ms cubic-bezier(0.22,1,0.36,1); }
        @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.25} }
        .blink { animation: blink 2.2s ease-in-out infinite; }

        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: #0b1728; }
        ::-webkit-scrollbar-thumb { background: #263a55; border-radius: 999px; }
        ::-webkit-scrollbar-thumb:hover { background: #00c2ff; }

        :focus-visible { outline: 2px solid #00c2ff; outline-offset: 2px; }
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; transition-duration: .01ms !important; }
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen">

<script>
    // Shared external-paste detection, used by the report editor and the case
    // investigation answer fields. A browser can never tell WHICH website text
    // came from — clipboard APIs don't expose that — but copying visible text
    // out of any rendered webpage (or Word/Google Docs) always carries a
    // "text/html" clipboard entry alongside the plain text, where plain typing
    // or a paste from a bare-text source never does. That's the signal we use.
    function extractPasteInfo(e) {
        const cd = e.clipboardData || window.clipboardData;
        const text = cd ? (cd.getData('text/plain') || cd.getData('text') || '') : '';
        const html = cd ? (cd.getData('text/html') || '') : '';
        const richPattern = /<a\s|<img\s|<table|<ul|<ol|<b>|<i>|<strong|<em|style=|class=|<div|<p[ >]/i;
        return { text, isRich: !!html && richPattern.test(html) };
    }

    // Brackets the pasted text with a visible marker directly in the field,
    // rather than silently accepting or destroying it — the student keeps
    // their pasted words, but it's now unmistakably flagged in the field
    // itself (and stays that way if they submit without editing it out).
    function insertWithExternalMarker(el, text) {
        const marker = `[⚠ PASTED FROM EXTERNAL SOURCE] ${text.trim()} [END PASTED]`;
        const start = el.selectionStart ?? el.value.length;
        const end = el.selectionEnd ?? el.value.length;
        el.value = el.value.slice(0, start) + marker + el.value.slice(end);
        const pos = start + marker.length;
        el.selectionStart = el.selectionEnd = pos;
        el.dispatchEvent(new Event('input', { bubbles: true }));
    }

    // Attach to any container the student is legitimately allowed to copy
    // from within the app — evidence panels, the scenario/instructions text,
    // their own previously-written answers carried into the report, etc.
    // Copying out of a rendered page normally carries BOTH plain text and an
    // HTML clipboard entry (that's true even for our own pages, which is
    // exactly what extractPasteInfo() treats as the "external" signal) — this
    // intercepts the copy and writes plain text only, so pulling a value out
    // of the evidence panel and into an answer never gets flagged, while a
    // paste from an actual outside website (which we have no control over)
    // still carries its own HTML and is still caught.
    function markInternalCopySource(el) {
        el.addEventListener('copy', (e) => {
            const selection = window.getSelection().toString();
            if (!selection) return;
            e.clipboardData.setData('text/plain', selection);
            e.preventDefault();
        });
    }
</script>

@auth
{{-- Impersonation banner --}}
@if(session('impersonator_id'))
<div class="bg-red text-white px-6 py-2.5 flex items-center justify-between gap-4 sticky top-0 z-50"
    x-data="impersonationSwitcher()" @keydown.escape.window="open = false">
    <p class="text-[13px] font-medium">
        <span class="font-mono text-[10px] uppercase tracking-[0.14em] border border-white/40 px-2 py-1 mr-2">View as</span>
        You are viewing the platform as <strong>{{ auth()->user()->name }}</strong>. Actions here are recorded.
    </p>
    <div class="flex items-center gap-2 relative">
        <div class="relative">
            <input type="text" x-model="query" @focus="openList()" @input="openList()"
                placeholder="Switch to another student…"
                class="bg-white/15 placeholder-white/70 text-white text-[12.5px] px-3 py-1.5 border border-white/30 focus:outline-none focus:bg-white/25 transition w-56">
            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                class="absolute right-0 mt-1 w-72 max-h-72 overflow-y-auto bg-surface border border-edge text-fg shadow-lg z-50">
                <template x-if="loading">
                    <p class="px-3 py-3 text-[12px] text-fg-3">Loading students…</p>
                </template>
                <template x-if="!loading && filtered().length === 0">
                    <p class="px-3 py-3 text-[12px] text-fg-3">No matching students.</p>
                </template>
                <template x-for="s in filtered()" :key="s.id">
                    <form method="POST" :action="switchUrl(s.id)">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 text-[12.5px] hover:bg-raised transition flex items-center justify-between gap-2">
                            <span x-text="s.name"></span>
                            <span class="font-mono text-[10px] text-fg-3" x-text="s.identifier"></span>
                        </button>
                    </form>
                </template>
            </div>
        </div>
        <form method="POST" action="{{ route('impersonate.stop') }}">
            @csrf
            <button class="bg-white text-red font-semibold text-[12px] px-4 py-1.5 hover:opacity-85 transition">
                Exit view-as
            </button>
        </form>
    </div>
</div>
<script>
    function impersonationSwitcher() {
        return {
            open: false, loading: false, query: '', students: [],
            openList() {
                this.open = true;
                if (this.students.length || this.loading) return;
                this.loading = true;
                fetch('{{ route('impersonate.students') }}')
                    .then(r => r.json())
                    .then(data => { this.students = data; this.loading = false; })
                    .catch(() => { this.loading = false; });
            },
            filtered() {
                const q = this.query.trim().toLowerCase();
                const list = q
                    ? this.students.filter(s => s.name.toLowerCase().includes(q) || (s.identifier || '').toLowerCase().includes(q))
                    : this.students;
                return list.slice(0, 20);
            },
            switchUrl(id) {
                return '{{ url('/impersonate/switch') }}/' + id;
            },
        }
    }
</script>
@endif

@php
    $headerUser = auth()->user();
    $headerNotifications = collect();
    if ($headerUser->role === 'lecturer') {
        $headerNotifications = \App\Models\CaseEnrollment::whereHas('forensicCase', fn($q) => $q->where('lecturer_id', $headerUser->id))
            ->where('status', 'submitted')
            ->with(['student', 'forensicCase'])
            ->latest('submitted_at')
            ->take(6)
            ->get()
            ->map(fn($e) => [
                'title' => $e->student->name . ' submitted a report',
                'sub'   => $e->forensicCase->title,
                'time'  => $e->submitted_at,
                'url'   => route('lecturer.report.show', [$e->forensicCase, $e]),
            ]);
    } elseif ($headerUser->role === 'student') {
        $headerNotifications = \App\Models\CaseEnrollment::where('student_id', $headerUser->id)
            ->where('status', 'graded')
            ->with(['forensicCase', 'report'])
            ->latest('updated_at')
            ->take(6)
            ->get()
            ->map(fn($e) => [
                'title' => 'Report graded: ' . $e->forensicCase->title,
                'sub'   => $e->report?->marks !== null ? $e->report->marks.'/'.$e->forensicCase->total_marks : null,
                'time'  => $e->updated_at,
                'url'   => route('student.report.show', $e->forensicCase),
            ]);
    }
@endphp

<div class="flex h-screen overflow-hidden">
    <img src="{{ asset('images/logo-icon.png') }}" alt=""
        class="pointer-events-none fixed bottom-0 right-0 w-[34rem] h-[34rem] object-contain opacity-[0.05] -z-10">

    @include('layouts.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="relative bg-surface border-b border-edge px-7 py-5 flex items-center justify-between flex-shrink-0 gap-6">
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <img src="{{ asset('images/logo-icon.png') }}" alt="" class="absolute -right-6 -top-10 w-40 h-40 object-contain opacity-[0.07]">
            </div>
            <div class="relative min-w-0">
                <p class="flex items-center gap-1.5 eyebrow mb-1.5">
                    @yield('eyebrow', 'AI-ForensicEdu')
                    <i data-lucide="chevron-right" class="w-3 h-3 text-fg-3"></i>
                </p>
                <h1 class="text-[26px] font-bold text-heading leading-tight">@yield('page-title', 'Dashboard')</h1>
                @hasSection('page-subtitle')
                <p class="text-[13px] text-fg-2 mt-1">@yield('page-subtitle')</p>
                @endif
            </div>
            <div class="relative flex items-center gap-4 flex-shrink-0">
                <div class="relative" x-data="{ open: false }">
                    <button type="button" @click="open = !open"
                        class="relative w-9 h-9 flex items-center justify-center rounded-full border border-edge text-fg-2 hover:text-fg hover:border-blue transition">
                        <i data-lucide="bell" class="w-4 h-4"></i>
                        @if($headerNotifications->count())
                        <span class="absolute top-1.5 right-1.5 w-1.5 h-1.5 rounded-full bg-red"></span>
                        @endif
                    </button>
                    <div x-show="open" x-cloak @click.outside="open = false" x-transition
                        class="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto bg-surface border border-edge rounded-xl shadow-lg z-50">
                        <div class="px-4 py-3 border-b border-edge">
                            <p class="text-[13px] font-semibold text-fg">Notifications</p>
                        </div>
                        @forelse($headerNotifications as $n)
                        <a href="{{ $n['url'] }}" class="block px-4 py-3 border-b border-edge last:border-0 hover:bg-raised transition">
                            <p class="text-[12.5px] font-medium text-fg">{{ $n['title'] }}</p>
                            @if($n['sub'])<p class="text-[11.5px] text-fg-2 mt-0.5">{{ $n['sub'] }}</p>@endif
                            <p class="text-[10.5px] text-fg-3 mt-1">{{ $n['time']?->diffForHumans() }}</p>
                        </a>
                        @empty
                        <p class="px-4 py-6 text-[12.5px] text-fg-3 text-center">Nothing new right now.</p>
                        @endforelse
                    </div>
                </div>
                <div class="text-right hidden sm:block">
                    <p class="text-[13px] font-medium text-fg leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] font-semibold text-fg-3 uppercase tracking-wider">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn-ghost !rounded-full px-4 py-2 inline-flex items-center gap-1.5">
                        <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign out
                    </button>
                </form>
            </div>
        </header>

        @if(session('success') || $errors->any())
        <div class="px-7 pt-5">
            @if(session('success'))
                <div class="bg-blue-soft border-l-2 border-blue px-4 py-3 mb-3 flex items-start gap-2.5">
                    <span class="seal seal-graded mt-0.5">OK</span>
                    <p class="text-[13.5px] text-fg leading-relaxed">{{ session('success') }}</p>
                </div>
            @endif
            @if($errors->any())
                <div class="bg-red-soft border-l-2 border-red px-4 py-3 mb-3 flex items-start gap-2.5">
                    <span class="seal seal-alert mt-0.5">ERR</span>
                    <ul class="text-[13.5px] text-fg space-y-0.5 leading-relaxed">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @endif
        </div>
        @endif

        <main class="flex-1 overflow-y-auto px-7 pb-8">
            @yield('content')
        </main>
    </div>
</div>
@else
    @yield('content')
@endauth

@stack('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });
</script>
</body>
</html>
