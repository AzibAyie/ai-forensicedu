{{-- CLASS-PERMISSION POP-UP — fires whenever the student isn't approved into a
     lecturer's class yet, has a class-switch pending, or the last thing that
     happened to a request (join or switch) hasn't been acknowledged. Re-appears
     whenever the underlying state changes — a new pending request, a fresh
     rejection, a fresh validation error. Included on every page that computes
     $classStatus, so it fires immediately on whichever page the student took
     the action from, not just the next time they visit the Case Board. --}}
@php
    $notice = match(true) {
        $classStatus['state'] === 'pending' => 'pending',
        $classStatus['state'] === 'rejected' => 'rejected',
        $classStatus['state'] === 'none' => 'none',
        $classStatus['state'] === 'approved' && $classStatus['switch_request'] => 'switch_pending',
        $classStatus['state'] === 'approved' && $classStatus['switch_decision'] => 'switch_rejected',
        default => null,
    };
    $noticeRecord = $classStatus['request'] ?? $classStatus['switch_request'] ?? $classStatus['switch_decision'];
    $noticeSig = $notice . ':' . $noticeRecord?->id;
@endphp
@if($notice)
<div x-data="{ show: false }"
     x-init="
        if (localStorage.getItem('classStatusSeen') !== '{{ $noticeSig }}' || {{ $errors->has('class_code') ? 'true' : 'false' }}) { show = true; }
     ">
    <div x-show="show" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgb(0 0 0 / 0.6);">
        <div @click.outside="show = false; localStorage.setItem('classStatusSeen', '{{ $noticeSig }}')"
             x-transition class="bg-surface border border-edge rounded-2xl max-w-md w-full p-6 relative shadow-2xl">
            <button @click="show = false; localStorage.setItem('classStatusSeen', '{{ $noticeSig }}')"
                class="absolute top-4 right-4 text-fg-3 hover:text-fg">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>

            @if($notice === 'pending')
                <div class="w-11 h-11 rounded-xl bg-warning-soft flex items-center justify-center mb-4">
                    <i data-lucide="clock" class="w-5 h-5 text-warning"></i>
                </div>
                <h3 class="font-display text-[17px] font-semibold text-heading mb-2">Waiting for approval</h3>
                <p class="text-[13.5px] text-fg-2 leading-relaxed mb-5">
                    You need permission from your lecturer before you can see their cases. Your request to join
                    <strong class="text-fg">{{ $classStatus['lecturer']->name }}</strong>'s class has been sent —
                    you'll get access as soon as they approve it.
                </p>
                <button @click="show = false; localStorage.setItem('classStatusSeen', '{{ $noticeSig }}')"
                    class="btn-primary w-full text-[13px]">Got it</button>

            @elseif($notice === 'switch_pending')
                <div class="w-11 h-11 rounded-xl bg-warning-soft flex items-center justify-center mb-4">
                    <i data-lucide="clock" class="w-5 h-5 text-warning"></i>
                </div>
                <h3 class="font-display text-[17px] font-semibold text-heading mb-2">Switch request pending</h3>
                <p class="text-[13.5px] text-fg-2 leading-relaxed mb-5">
                    Your request to switch to <strong class="text-fg">{{ $classStatus['switch_request']->lecturer->name }}</strong>
                    is waiting for their approval. You'll keep your current access to
                    <strong class="text-fg">{{ $classStatus['lecturer']->name }}</strong> until then.
                </p>
                <button @click="show = false; localStorage.setItem('classStatusSeen', '{{ $noticeSig }}')"
                    class="btn-primary w-full text-[13px]">Got it</button>

            @elseif($notice === 'switch_rejected')
                <div class="w-11 h-11 rounded-xl bg-red-soft flex items-center justify-center mb-4">
                    <i data-lucide="x-circle" class="w-5 h-5 text-red"></i>
                </div>
                <h3 class="font-display text-[17px] font-semibold text-heading mb-2">Switch request declined</h3>
                <p class="text-[13.5px] text-fg-2 leading-relaxed mb-5">
                    Your request to switch to <strong class="text-fg">{{ $classStatus['switch_decision']->lecturer->name }}</strong>
                    wasn't approved. You're still enrolled with <strong class="text-fg">{{ $classStatus['lecturer']->name }}</strong> —
                    try a different code below if you'd like to switch again.
                </p>
                @if($errors->has('class_code'))
                    <p class="text-[12.5px] text-red mb-3">{{ $errors->first('class_code') }}</p>
                @endif
                <form method="POST" action="{{ route('student.join-class') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="class_code" required maxlength="8" placeholder="e.g. 7K2PXQ"
                        class="fld font-mono uppercase" style="letter-spacing:0.15em">
                    <button type="submit" class="btn-primary text-[13px] px-4 flex-shrink-0">Send request</button>
                </form>

            @else
                <div class="w-11 h-11 rounded-xl {{ $notice === 'rejected' ? 'bg-red-soft' : 'bg-blue-soft' }} flex items-center justify-center mb-4">
                    <i data-lucide="{{ $notice === 'rejected' ? 'x-circle' : 'key-round' }}" class="w-5 h-5 {{ $notice === 'rejected' ? 'text-red' : 'text-blue' }}"></i>
                </div>
                @if($notice === 'rejected')
                    <h3 class="font-display text-[17px] font-semibold text-heading mb-2">Request declined</h3>
                    <p class="text-[13.5px] text-fg-2 leading-relaxed mb-5">
                        Your request to join <strong class="text-fg">{{ $classStatus['lecturer']->name }}</strong>'s class
                        wasn't approved. Check the class code with your lecturer, or try a different one below.
                    </p>
                @else
                    <h3 class="font-display text-[17px] font-semibold text-heading mb-2">You need permission to join a class</h3>
                    <p class="text-[13.5px] text-fg-2 leading-relaxed mb-5">
                        Cases are only visible to students their lecturer has approved. Enter the class code your
                        lecturer gave you — they'll need to accept your request before you can start investigating.
                    </p>
                @endif
                @if($errors->has('class_code'))
                    <p class="text-[12.5px] text-red mb-3">{{ $errors->first('class_code') }}</p>
                @endif
                <form method="POST" action="{{ route('student.join-class') }}" class="flex gap-2">
                    @csrf
                    <input type="text" name="class_code" required maxlength="8" placeholder="e.g. 7K2PXQ"
                        class="fld font-mono uppercase" style="letter-spacing:0.15em">
                    <button type="submit" class="btn-primary text-[13px] px-4 flex-shrink-0">Send request</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endif
