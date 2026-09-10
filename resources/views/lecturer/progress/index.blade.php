@extends('layouts.app')
@section('title', 'Student Progress')
@section('eyebrow', 'Cohort')
@section('page-title', 'Student Progress')

@section('content')
<div class="pt-6 space-y-6">

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-px bg-edge border border-edge rounded-xl overflow-hidden">
        @foreach([
            ['Assigned to you', $summary['total'], 'students'],
            ['Actively working', $summary['active'], 'case open now'],
            ['Stalled', $summary['stalled'], 'no activity 5+ days'],
            ['Not started', $summary['idle'], 'never opened a case'],
        ] as $s)
        <div class="bg-surface px-5 py-4">
            <p class="eyebrow mb-2">{{ $s[0] }}</p>
            <p class="font-display text-[26px] font-semibold {{ $s[0] === 'Stalled' && $s[1] > 0 ? 'text-red' : 'text-fg' }} leading-none">{{ $s[1] }}</p>
            <p class="text-[11px] text-fg-3 mt-1.5">{{ $s[2] }}</p>
        </div>
        @endforeach
    </section>

    @if($rows->isEmpty())
    <div class="bg-surface border border-edge px-6 py-16 text-center">
        <p class="font-display text-[16px] font-semibold text-fg">No students assigned</p>
        <p class="text-[13px] text-fg-2 mt-1.5">
            Students are linked to you on their profile. Assign them from
            <a href="{{ route('lecturer.students') }}" class="text-blue hover:underline">My Students</a>.
        </p>
    </div>
    @else
    <section class="bg-surface border border-edge">
        <table class="w-full text-[13px]">
            <thead>
                <tr class="border-b border-edge">
                    <th class="eyebrow text-left px-5 py-3">Student</th>
                    <th class="eyebrow text-left px-5 py-3">Currently working on</th>
                    <th class="eyebrow text-left px-5 py-3">Progress</th>
                    <th class="eyebrow text-center px-5 py-3">Completed</th>
                    <th class="eyebrow text-center px-5 py-3">Average</th>
                    <th class="eyebrow text-left px-5 py-3">Last active</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                @php $s = $r['student']; @endphp
                <tr class="border-b border-edge last:border-0 hover:bg-raised">
                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 border border-edge flex items-center justify-center flex-shrink-0">
                                <span class="font-mono text-[10px] font-semibold text-blue">{{ strtoupper(substr($s->name, 0, 2)) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-fg leading-tight">{{ $s->name }}</p>
                                <p class="font-mono text-[10.5px] text-fg-3">{{ $s->student_id ?? $s->email }}</p>
                            </div>
                        </div>
                    </td>

                    <td class="px-5 py-3.5">
                        @if($r['active'])
                            <p class="text-fg text-[12.5px] leading-tight">{{ $r['active']->forensicCase->title }}</p>
                            <p class="font-mono text-[10.5px] text-fg-3 mt-0.5">
                                opened {{ $r['active']->started_at?->diffForHumans() }}
                            </p>
                        @elseif($r['idle'])
                            <span class="seal seal-draft">Not started</span>
                        @else
                            <span class="font-mono text-[11px] text-fg-3">Between cases</span>
                        @endif
                    </td>

                    <td class="px-5 py-3.5">
                        @if($r['active'])
                        <div class="flex items-center gap-2">
                            <div class="h-[3px] bg-edge w-20">
                                <div class="h-[3px] {{ $r['stalled'] ? 'bg-red' : 'bg-blue' }} progress-bar"
                                    style="width: {{ $r['active']->progress_percent }}%"></div>
                            </div>
                            <span class="font-mono text-[10.5px] text-fg-3">{{ $r['active']->progress_percent }}%</span>
                        </div>
                        @else
                            <span class="text-fg-3">—</span>
                        @endif
                    </td>

                    <td class="px-5 py-3.5 text-center font-mono text-[12px] text-fg">
                        {{ $r['completed'] }}<span class="text-fg-3">/{{ $r['enrolled'] }}</span>
                    </td>

                    <td class="px-5 py-3.5 text-center">
                        @if($r['avg'] !== null)
                            <span class="font-display font-semibold {{ $r['avg'] < 50 ? 'text-red' : 'text-fg' }}">{{ $r['avg'] }}%</span>
                        @else
                            <span class="text-fg-3">—</span>
                        @endif
                    </td>

                    <td class="px-5 py-3.5">
                        @if($r['last_seen'])
                            <p class="font-mono text-[10.5px] {{ $r['stalled'] ? 'text-red' : 'text-fg-3' }}">
                                {{ $r['last_seen']->diffForHumans() }}
                            </p>
                            @if($r['stalled'])<span class="seal seal-alert mt-1">Stalled</span>@endif
                        @else
                            <span class="font-mono text-[10.5px] text-fg-3">Never</span>
                        @endif
                    </td>

                    <td class="px-5 py-3.5">
                        <div class="flex items-center gap-px justify-end">
                            <a href="{{ route('lecturer.progress.show', $s) }}" class="btn-ghost px-3 py-1.5">Detail</a>
                            <form method="POST" action="{{ route('lecturer.impersonate.start', $s) }}"
                                onsubmit="return confirm('View the platform as {{ addslashes($s->name) }}? This is recorded in the activity log.')">
                                @csrf
                                <button class="btn-ghost px-3 py-1.5">View as</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </section>
    @endif
</div>
@endsection
