@php $role = auth()->user()->role; @endphp

<aside class="bg-sidebar border-r border-edge w-60 flex-shrink-0 flex flex-col">

    <div class="px-5 py-5 border-b border-white/10">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 bg-blue flex items-center justify-center flex-shrink-0 rounded-lg">
                <span class="font-display font-bold text-[13px] text-base">FE</span>
            </div>
            <div class="leading-tight">
                <p class="font-display text-[13.5px] font-semibold text-white">AI-ForensicEDU</p>
                <p class="text-[9px] font-semibold text-white/55 uppercase tracking-[0.08em]">{{ $role }} console</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 py-4 overflow-y-auto">
        @if($role === 'student')
            <p class="eyebrow px-5 mb-2 !text-white/45">Investigation</p>
            <a href="{{ route('student.dashboard') }}" class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <i data-lucide="file-text"></i> Case Board
            </a>
            @php $active = auth()->user()->currentEnrollment(); @endphp
            @if($active)
            <a href="{{ route('student.case.show', $active->forensicCase) }}" class="nav-link {{ request()->routeIs('student.case.show') ? 'active' : '' }}">
                <i data-lucide="search"></i> Active Case
            </a>
            <a href="{{ route('student.report.show', $active->forensicCase) }}" class="nav-link {{ request()->routeIs('student.report.show') ? 'active' : '' }}">
                <i data-lucide="square-pen"></i> Write Report
            </a>
            @endif

            <p class="eyebrow px-5 mb-2 mt-6 !text-white/45">Account</p>
            <a href="{{ route('student.record') }}" class="nav-link {{ request()->routeIs('student.record') ? 'active' : '' }}">
                <i data-lucide="award"></i> My Record
            </a>
            <a href="{{ route('student.profile') }}" class="nav-link {{ request()->routeIs('student.profile') ? 'active' : '' }}">
                <i data-lucide="user"></i> Profile
            </a>

        @elseif($role === 'lecturer')
            <p class="eyebrow px-5 mb-2 !text-white/45">Cases</p>
            <a href="{{ route('lecturer.dashboard') }}" class="nav-link {{ request()->routeIs('lecturer.dashboard') ? 'active' : '' }}">
                <i data-lucide="file-text"></i> Case Registry
            </a>
            <a href="{{ route('lecturer.case.create') }}" class="nav-link {{ request()->routeIs('lecturer.case.create') ? 'active' : '' }}">
                <i data-lucide="plus"></i> Build Case
            </a>

            <p class="eyebrow px-5 mb-2 mt-6 !text-white/45">Cohort</p>
            <a href="{{ route('lecturer.progress') }}" class="nav-link {{ request()->routeIs('lecturer.progress*') ? 'active' : '' }}">
                <i data-lucide="chart-no-axes-column"></i> Progress
            </a>
            <a href="{{ route('lecturer.gradebook') }}" class="nav-link {{ request()->routeIs('lecturer.gradebook') ? 'active' : '' }}">
                <i data-lucide="layout-grid"></i> Gradebook
            </a>
            <a href="{{ route('lecturer.students') }}" class="nav-link {{ request()->routeIs('lecturer.students') ? 'active' : '' }}">
                <i data-lucide="users"></i> My Students
            </a>

            <p class="eyebrow px-5 mb-2 mt-6 !text-white/45">Account</p>
            <a href="{{ route('lecturer.profile') }}" class="nav-link {{ request()->routeIs('lecturer.profile') ? 'active' : '' }}">
                <i data-lucide="user"></i> Profile
            </a>
        @endif
    </nav>

    <div class="px-5 py-4 border-t border-white/15">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 border border-white/30 flex items-center justify-center flex-shrink-0 rounded-full">
                <span class="font-mono text-[10px] font-semibold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
            </div>
            <div class="min-w-0 leading-tight">
                <p class="text-[12px] font-medium text-white truncate">{{ auth()->user()->name }}</p>
                <p class="font-mono text-[9.5px] text-white/55 truncate">
                    {{ auth()->user()->student_id ?? auth()->user()->staff_id ?? auth()->user()->email }}
                </p>
                @if($role === 'student')
                <span class="seal seal-open mt-1.5 !text-[8px] !px-1.5 !py-0.5 !bg-white/15 !text-white !border-white/30">{{ auth()->user()->investigatorRank()['label'] }}</span>
                @endif
            </div>
        </div>
    </div>
</aside>
