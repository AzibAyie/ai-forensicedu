@extends('layouts.app')
@section('title', $student->name)
@section('eyebrow', 'Cohort · Progress')
@section('page-title', $student->name)

@section('content')
<div class="pt-6 space-y-6">

    <a href="{{ route('lecturer.progress') }}" class="text-[13px] text-white/70 hover:text-white">← Back to progress</a>

    <section class="bg-surface border border-edge px-6 py-5 flex flex-wrap items-center gap-5">
        <div class="w-12 h-12 border border-blue flex items-center justify-center flex-shrink-0">
            <span class="font-mono text-[15px] font-semibold text-blue">{{ strtoupper(substr($student->name, 0, 2)) }}</span>
        </div>
        <div class="min-w-0">
            <h2 class="font-display text-[18px] font-semibold text-fg leading-tight">{{ $student->name }}</h2>
            <p class="font-mono text-[11px] text-fg-3 mt-0.5">
                {{ $student->student_id ?? 'NO ID' }} · {{ $student->program ?? 'Programme not set' }} · {{ $student->email }}
            </p>
        </div>
        <form method="POST" action="{{ route('lecturer.impersonate.start', $student) }}" class="ml-auto"
            onsubmit="return confirm('View as {{ addslashes($student->name) }}? This is recorded.')">
            @csrf
            <button class="btn-primary text-[13px] px-4 py-2.5">View as this student</button>
        </form>
    </section>

    {{-- PROGRESS CHART --}}
    <section class="bg-surface border border-edge">
        <div class="px-6 py-4 border-b border-edge">
            <h3 class="font-display text-[14px] font-semibold text-fg">Progress breakdown</h3>
            <p class="text-[11.5px] text-fg-3 mt-0.5">Where this student's cases currently stand</p>
        </div>
        <div class="px-6 py-5">
            @if(array_sum($stageCounts) > 0)
                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <div style="height: 200px; width: 200px" class="flex-shrink-0">
                        <canvas id="studentProgressChart"></canvas>
                    </div>
                    <div class="flex-1 w-full space-y-2.5">
                        @foreach($stageCounts as $stage => $count)
                        <div class="flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 flex-shrink-0" style="background: {{ ['Graded'=>'#2151E5','Awaiting grade'=>'#E22323','In progress'=>'#000000','Not started'=>'#D6DEEC'][$stage] }}"></span>
                            <span class="text-[12.5px] text-fg flex-1">{{ $stage }}</span>
                            <span class="font-mono text-[12px] text-fg-3">{{ $count }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="text-[13px] text-fg-2 py-6 text-center">This student has not opened any of your cases yet.</p>
            @endif
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <section class="xl:col-span-2 bg-surface border border-edge">
            <div class="px-6 py-4 border-b border-edge">
                <h3 class="font-display text-[14px] font-semibold text-fg">Case history</h3>
            </div>
            @forelse($enrollments as $e)
            <div class="px-6 py-4 border-b border-edge last:border-0">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2.5 mb-1">
                            <span class="font-mono text-[10px] text-fg-3 tracking-wider">
                                CASE-{{ str_pad($e->forensicCase->id, 3, '0', STR_PAD_LEFT) }}
                            </span>
                            <span class="seal {{ match($e->status) {
                                'graded' => 'seal-graded', 'submitted' => 'seal-open', default => 'seal-draft' } }}">
                                {{ ucfirst(str_replace('_',' ', $e->status)) }}
                            </span>
                        </div>
                        <p class="font-display text-[14px] font-semibold text-fg leading-tight">{{ $e->forensicCase->title }}</p>
                        <p class="font-mono text-[10.5px] text-fg-3 mt-1">
                            {{ $e->answers->count() }}/{{ $e->forensicCase->questions()->count() }} questions answered
                            @if($e->started_at) · started {{ $e->started_at->format('d M') }} @endif
                            @if($e->submitted_at) · filed {{ $e->submitted_at->format('d M') }} @endif
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        @if($e->report?->marks !== null)
                            <p class="font-display text-[18px] font-semibold text-fg">
                                {{ $e->report->marks }}<span class="text-[12px] text-fg-3">/{{ $e->forensicCase->total_marks }}</span>
                            </p>
                            <a href="{{ route('lecturer.report.show', [$e->forensicCase, $e]) }}"
                                class="font-mono text-[10px] text-blue hover:underline uppercase tracking-wider">Open report</a>
                        @elseif($e->isSubmitted())
                            <a href="{{ route('lecturer.report.show', [$e->forensicCase, $e]) }}" class="btn-ghost px-3 py-1.5">Grade</a>
                        @else
                            <div class="h-[3px] bg-edge w-20 mb-1">
                                <div class="h-[3px] bg-blue" style="width: {{ $e->progress_percent }}%"></div>
                            </div>
                            <span class="font-mono text-[10px] text-fg-3">{{ $e->progress_percent }}%</span>
                        @endif
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-[13px] text-fg-2">This student has not opened any of your cases yet.</p>
            </div>
            @endforelse
        </section>

        <section class="bg-surface border border-edge">
            <div class="px-6 py-4 border-b border-edge">
                <h3 class="font-display text-[14px] font-semibold text-fg">Activity trail</h3>
                <p class="text-[11.5px] text-fg-3 mt-0.5">Last 40 recorded actions</p>
            </div>
            <div class="max-h-[520px] overflow-y-auto">
                @forelse($activity as $log)
                <div class="px-5 py-2.5 border-b border-edge last:border-0">
                    <div class="flex items-baseline gap-2.5">
                        <span class="font-mono text-[10px] text-fg-3 flex-shrink-0">{{ $log->created_at->format('d/m H:i') }}</span>
                        <span class="font-mono text-[10px] text-blue uppercase tracking-wide">{{ str_replace('_',' ', $log->action) }}</span>
                    </div>
                    @if($log->description)
                    <p class="text-[11.5px] text-fg-2 mt-0.5 leading-snug">{{ $log->description }}</p>
                    @endif
                </div>
                @empty
                <div class="px-5 py-10 text-center">
                    <p class="text-[12.5px] text-fg-3">No activity recorded.</p>
                </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
@if(array_sum($stageCounts) > 0)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('studentProgressChart');
    if (!ctx) return;
    const stageCounts = @json($stageCounts);
    const colors = { 'Graded': '#2151E5', 'Awaiting grade': '#E22323', 'In progress': '#000000', 'Not started': '#D6DEEC' };
    const labels = Object.keys(stageCounts).filter(k => stageCounts[k] > 0);

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: labels.map(l => stageCounts[l]),
                backgroundColor: labels.map(l => colors[l]),
                borderColor: '#FFFFFF',
                borderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (item) => `${item.label}: ${item.raw}`,
                    }
                }
            }
        }
    });
});
</script>
@endif
@endpush
