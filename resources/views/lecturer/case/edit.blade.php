@extends('layouts.app')
@section('title', 'Edit Case')
@section('page-title', 'Edit Case')
@section('page-subtitle', $forensicCase->title)
@section('content')
<div class="py-4 max-w-3xl">
    <form method="POST" action="{{ route('lecturer.case.update', $forensicCase) }}" class="space-y-5" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div class="bg-surface  border border-edge  p-6 space-y-4">
            <h3 class="font-semibold text-fg pb-2 border-b">Edit Case Details</h3>
            <div>
                <label class="block text-xs font-medium text-fg-2 mb-1">Title</label>
                <input type="text" name="title" value="{{ $forensicCase->title }}" required class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:border-blue">
            </div>
            <div>
                <label class="block text-xs font-medium text-fg-2 mb-1">Description</label>
                <input type="text" name="description" value="{{ $forensicCase->description }}" required class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:border-blue">
            </div>
            <div>
                <label class="block text-xs font-medium text-fg-2 mb-1">Scenario</label>
                <textarea name="scenario" required rows="5" class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:border-blue resize-none">{{ $forensicCase->scenario }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-fg-2 mb-1">Password (leave blank to keep current)</label>
                <input type="text" name="password" placeholder="Enter new password or leave blank" class="w-full border border-edge bg-input text-fg px-3 py-2 text-sm focus:outline-none focus:border-blue">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="eyebrow block mb-1.5">Opens at</label>
                    <input type="datetime-local" name="publish_at" class="fld"
                        value="{{ $forensicCase->publish_at?->format('Y-m-d\TH:i') }}">
                </div>
                <div>
                    <label class="eyebrow block mb-1.5">Closes at</label>
                    <input type="datetime-local" name="close_at" class="fld"
                        value="{{ $forensicCase->close_at?->format('Y-m-d\TH:i') }}">
                </div>
            </div>
            <div>
                <label class="eyebrow block mb-1.5">Question sheet (PDF)</label>
                @if($forensicCase->question_pdf_path)
                <p class="text-[12px] text-fg-2 mb-2">
                    Current: {{ $forensicCase->question_pdf_name }} ·
                    <a href="{{ route('lecturer.case.questions', $forensicCase) }}" class="text-blue hover:underline">download</a>
                </p>
                @endif
                <input type="file" name="question_pdf" accept="application/pdf"
                    class="fld file:mr-3 file:border-0 file:bg-blue file:text-base file:px-3 file:py-1.5 file:text-[12px] file:font-semibold file:cursor-pointer">
            </div>

            <div class="flex items-center gap-6">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_locked" class="rounded text-blue" {{ $forensicCase->is_locked ? 'checked' : '' }}>
                    <span class="text-sm text-fg">Case is locked</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_published" class="rounded text-blue" {{ $forensicCase->is_published ? 'checked' : '' }}>
                    <span class="text-sm text-fg">Published</span>
                </label>
            </div>
        </div>
        <div class="flex gap-3 justify-end">
            <a href="{{ route('lecturer.dashboard') }}" class="border border-white/30 text-white/80 px-5 py-2  text-sm hover:bg-white hover:text-black transition rounded-lg">Cancel</a>
            <button type="submit" class="btn-primary">Save Changes</button>
        </div>
    </form>
</div>
@endsection
