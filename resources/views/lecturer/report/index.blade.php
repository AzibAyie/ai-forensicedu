@extends('layouts.app')
@section('title', 'Student Reports')
@section('page-title', 'Student Reports')
@section('page-subtitle', $forensicCase->title)

@section('content')
<div class="py-4 space-y-5">

    {{-- Case Summary --}}
    <div class="bg-surface  border border-edge  p-5">
        <div class="flex items-center gap-4">
            <span class="text-3xl">{{ $forensicCase->incident_icon }}</span>
            <div class="flex-1">
                <h3 class="font-semibold text-fg">{{ $forensicCase->title }}</h3>
                <p class="text-sm text-fg-2">{{ $forensicCase->incident_label }} · {{ ucfirst($forensicCase->difficulty) }}</p>
            </div>
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xl font-bold text-fg">{{ $enrollments->count() }}</p>
                    <p class="text-xs text-fg-3">Enrolled</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-blue">{{ $enrollments->whereIn('status', ['submitted','graded'])->count() }}</p>
                    <p class="text-xs text-fg-3">Submitted</p>
                </div>
                <div>
                    <p class="text-xl font-bold text-blue">{{ $enrollments->where('status', 'graded')->count() }}</p>
                    <p class="text-xs text-fg-3">Graded</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Students Table --}}
    <div class="bg-surface  border border-edge  overflow-hidden">
        <div class="px-5 py-4 border-b border-edge flex items-center justify-between">
            <h3 class="font-semibold text-fg">Enrolled Students</h3>
        </div>
        @if($enrollments->isEmpty())
        <div class="p-12 text-center text-fg-3">
            <p class="text-3xl mb-2">👥</p>
            <p>No students have enrolled in this case yet.</p>
        </div>
        @else
        <table class="w-full text-sm">
            <thead class="bg-base border-b border-edge">
                <tr>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">Student</th>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">ID</th>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">Status</th>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">Progress</th>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">Submitted</th>
                    <th class="text-left px-5 py-3 text-fg-2 font-medium">Score</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-edge">
                @foreach($enrollments as $enrollment)
                <tr class="hover:bg-base">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-soft flex items-center justify-center text-blue text-xs font-bold">
                                {{ strtoupper(substr($enrollment->student->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-medium text-fg">{{ $enrollment->student->name }}</p>
                                <p class="text-xs text-fg-3">{{ $enrollment->student->email }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-fg-2 font-mono text-xs">{{ $enrollment->student->student_id ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{
                            match($enrollment->status) {
                                'graded' => 'bg-blue-soft text-blue',
                                'submitted' => 'bg-blue-soft text-blue',
                                'in_progress' => 'bg-edge text-fg-2',
                                default => 'bg-edge text-fg-2',
                            }
                        }}">{{ ucfirst(str_replace('_', ' ', $enrollment->status)) }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <div class="bg-edge rounded-full h-1.5 w-16">
                                <div class="bg-blue h-1.5 rounded-full" style="width: {{ $enrollment->progress_percent }}%"></div>
                            </div>
                            <span class="text-xs text-fg-3">{{ $enrollment->progress_percent }}%</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-xs text-fg-3">{{ $enrollment->submitted_at?->format('d M Y, H:i') ?? '—' }}</td>
                    <td class="px-5 py-3 font-semibold text-fg">
                        {{ $enrollment->report?->marks !== null ? $enrollment->report->marks.'/'.$forensicCase->total_marks : '—' }}
                    </td>
                    <td class="px-5 py-3">
                        @if($enrollment->report)
                        <a href="{{ route('lecturer.report.show', [$forensicCase, $enrollment]) }}"
                            class="text-xs bg-blue-soft text-blue border border-blue px-3 py-1.5  hover:bg-blue-soft transition font-medium">
                            {{ $enrollment->status === 'graded' ? '👁 View' : '📋 Grade' }}
                        </a>
                        @else
                        <span class="text-xs text-fg-3">No report</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection
