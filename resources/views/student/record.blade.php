@extends('layouts.app')
@section('title', 'My Record')
@section('eyebrow', 'Account')
@section('page-title', 'Investigator Record')
@section('page-subtitle', 'Your investigation history, incident coverage, and earned milestones.')

@section('content')
<div class="pt-6 space-y-8">

    {{-- BADGE UNLOCK CELEBRATION --}}
    @if(count($newlyEarned))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 6000)" x-transition
        class="bg-blue text-base px-6 py-4 flex items-center gap-4 rounded-2xl">
        <span class="seal seal-active !bg-base !text-blue !border-base">Unlocked</span>
        <div class="flex-1">
            <p class="font-display text-[14px] font-semibold">
                {{ collect($newlyEarned)->pluck('name')->implode(', ') }}
            </p>
            <p class="text-[12px] text-base/70 mt-0.5">New milestone{{ count($newlyEarned) > 1 ? 's' : '' }} earned. Nice work.</p>
        </div>
        <button @click="show = false" class="text-base/70 hover:text-black"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    @endif

    {{-- IDENTITY BAR --}}
    <section class="relative bg-black text-white px-7 py-5 flex flex-wrap items-center gap-6 rounded-2xl overflow-hidden">
        <img src="{{ asset('images/logo-icon.png') }}" alt="" class="pointer-events-none absolute -right-8 -top-10 w-48 h-48 object-contain opacity-[0.09]">
        <div class="relative w-12 h-12 border border-blue flex items-center justify-center flex-shrink-0">
            <span class="font-mono text-[15px] font-semibold text-blue">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
        </div>
        <div class="min-w-0">
            <h2 class="font-display text-[18px] font-semibold leading-tight">{{ $user->name }}</h2>
            <p class="font-mono text-[11px] text-white/70 mt-0.5">
                {{ $user->student_id ?? 'NO ID' }} · {{ $user->program ?? 'Programme not set' }}
            </p>
        </div>
        <div class="ml-auto flex gap-8 text-right">
            <div>
                <p class="font-display text-[24px] font-semibold text-blue leading-none">{{ $stats['completed'] }}</p>
                <p class="eyebrow !text-white/50 mt-1.5">Closed</p>
            </div>
            <div>
                <p class="font-display text-[24px] font-semibold text-blue leading-none">
                    {{ $stats['avg_score'] > 0 ? number_format($stats['avg_score'], 0) : '—' }}
                </p>
                <p class="eyebrow !text-white/50 mt-1.5">Avg score</p>
            </div>
            <div>
                <p class="font-display text-[24px] font-semibold text-blue leading-none">{{ $stats['best_score'] ?? '—' }}</p>
                <p class="eyebrow !text-white/50 mt-1.5">Best</p>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- SCORE TREND --}}
        <section class="xl:col-span-2 bg-surface border border-edge">
            <div class="px-6 py-4 border-b border-edge flex items-baseline justify-between">
                <h3 class="font-display text-[14px] font-semibold text-fg inline-flex items-center gap-2"><i data-lucide="trending-up" class="w-4 h-4 text-blue"></i> Score history</h3>
                <p class="font-mono text-[10.5px] text-fg-3">{{ count($scoreHistory) }} graded</p>
            </div>
            <div class="px-6 py-5">
                @if(count($scoreHistory) > 0)
                    <div style="height: 240px"><canvas id="scoreChart"></canvas></div>
                @else
                    <div class="py-16 text-center">
                        <p class="text-[13px] text-fg-2">No graded reports yet.</p>
                        <p class="text-[12px] text-fg-3 mt-1">Your score trend appears here once a lecturer grades your first report.</p>
                    </div>
                @endif
            </div>
        </section>

        {{-- COMPETENCY --}}
        <section class="bg-surface border border-edge">
            <div class="px-6 py-4 border-b border-edge">
                <h3 class="font-display text-[14px] font-semibold text-fg inline-flex items-center gap-2"><i data-lucide="target" class="w-4 h-4 text-ai"></i> Incident coverage</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                @foreach($competency as $type => $data)
                <div>
                    <div class="flex items-baseline justify-between mb-2">
                        <p class="text-[12.5px] font-medium text-fg">{{ $data['label'] }}</p>
                        <p class="font-mono text-[11px] text-fg-3">{{ $data['done'] }}/{{ $data['total'] }}</p>
                    </div>
                    <div class="h-[3px] bg-edge">
                        <div class="h-[3px] bg-blue progress-bar"
                            style="width: {{ $data['total'] > 0 ? ($data['done'] / $data['total'] * 100) : 0 }}%"></div>
                    </div>
                    @if($data['avg'] !== null)
                    <p class="font-mono text-[10.5px] text-fg-3 mt-1.5">avg {{ number_format($data['avg'], 0) }}%</p>
                    @endif
                </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- MILESTONES --}}
    <section>
        <h3 class="font-display text-[15px] font-semibold text-white mb-4 inline-flex items-center gap-2"><i data-lucide="award" class="w-4 h-4 text-warning"></i> Milestones</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-px bg-edge border border-edge">
            @foreach($milestones as $m)
            @php $isNew = collect($newlyEarned)->contains('mark', $m['mark']); @endphp
            <div class="bg-surface px-5 py-5 card-interactive {{ $m['earned'] ? '' : 'opacity-40' }} {{ $isNew ? 'flash-once' : '' }}">
                <div class="w-9 h-9 border {{ $m['earned'] ? 'border-blue bg-blue-soft' : 'border-edge' }} flex items-center justify-center mb-3">
                    <span class="font-mono text-[13px] {{ $m['earned'] ? 'text-blue' : 'text-fg-3' }}">{{ $m['mark'] }}</span>
                </div>
                <p class="text-[12.5px] font-semibold text-fg leading-tight">{{ $m['name'] }}</p>
                <p class="text-[11.5px] text-fg-2 mt-1 leading-snug">{{ $m['desc'] }}</p>
                @if($m['earned'])
                    <span class="seal seal-graded mt-3"><span class="w-1.5 h-1.5 rounded-full bg-current"></span> Earned</span>
                @else
                    <p class="font-mono text-[10px] text-fg-3 mt-3 uppercase tracking-wider">{{ $m['progress'] }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </section>

    {{-- INVESTIGATION LOG --}}
    <section>
        <div class="flex items-baseline justify-between mb-4">
            <h3 class="font-display text-[15px] font-semibold text-white inline-flex items-center gap-2"><i data-lucide="history" class="w-4 h-4 text-fg-3"></i> Recent activity</h3>
            <p class="font-mono text-[10.5px] text-white/60">{{ $stats['total_actions'] }} actions logged</p>
        </div>
        <div class="bg-surface border border-edge">
            @forelse($recentActivity as $log)
            <div class="px-6 py-3 border-b border-edge last:border-0 flex items-baseline gap-4">
                <span class="font-mono text-[10.5px] text-fg-3 w-28 flex-shrink-0">{{ $log->created_at->format('d M · H:i') }}</span>
                <span class="font-mono text-[10.5px] text-blue w-40 flex-shrink-0 uppercase tracking-wide">{{ str_replace('_', ' ', $log->action) }}</span>
                <span class="text-[12.5px] text-fg-2 leading-snug">{{ $log->description }}</span>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <p class="text-[13px] text-fg-2">No activity recorded yet.</p>
                <p class="text-[12px] text-fg-3 mt-1">Open a case to start building your investigation log.</p>
            </div>
            @endforelse
        </div>
    </section>

</div>
@endsection

@push('scripts')
@if(count($scoreHistory) > 0)
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('scoreChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json(array_column($scoreHistory, 'label')),
            datasets: [{
                label: 'Score (%)',
                data: @json(array_column($scoreHistory, 'score')),
                borderColor: '#00c2ff',
                backgroundColor: 'rgba(0,194,255,0.14)',
                borderWidth: 2,
                pointBackgroundColor: '#111d2e',
                pointBorderColor: '#00c2ff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.25,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true, max: 100,
                    grid: { color: 'rgba(234,242,255,0.08)' },
                    ticks: { font: { family: 'Inter', size: 10 }, color: 'rgba(234,242,255,0.55)', stepSize: 25 }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { family: 'Inter', size: 10 }, color: 'rgba(234,242,255,0.55)' }
                }
            }
        }
    });
});
</script>
@endif
@endpush
