{{-- Password field with a show/hide toggle. Params: name, label?, hint?, required?, minlength?, autocomplete? --}}
<div x-data="{ show: false }">
    @if(! empty($label))
    <label class="eyebrow block mb-1.5">{{ $label }}</label>
    @endif
    <div class="relative">
        <input :type="show ? 'text' : 'password'" name="{{ $name }}" id="{{ $id ?? $name }}"
            @if($required ?? false) required @endif
            @if(! empty($minlength)) minlength="{{ $minlength }}" @endif
            autocomplete="{{ $autocomplete ?? 'new-password' }}"
            class="fld pr-11">
        <button type="button" @click="show = !show" tabindex="-1"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-fg-3 hover:text-fg transition">
            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg x-show="show" x-cloak xmlns="http://www.w3.org/2000/svg" class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18" />
                <path d="M10.6 10.6a3 3 0 0 0 4.24 4.24" />
                <path d="M6.4 6.6C3.9 8.2 1.5 12 1.5 12s4 7.5 10.5 7.5c2.1 0 3.9-.5 5.4-1.3" />
                <path d="M17.6 17.5C19.9 15.9 22.5 12 22.5 12s-1-1.9-2.7-3.7" />
                <path d="M14.1 5.1C13.4 5 12.7 4.5 12 4.5c-.9 0-1.8.1-2.6.3" />
            </svg>
        </button>
    </div>
    @if(! empty($hint))
    <p class="text-[11px] text-fg-3 mt-1">{{ $hint }}</p>
    @endif
</div>
