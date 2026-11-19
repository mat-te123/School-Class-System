@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms
        x-init="setTimeout(() => show = false, 5000)" role="status" aria-live="polite"
        class="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-lg border border-emerald-200 border-l-4 bg-white p-4 text-emerald-950 shadow-lg sm:inset-x-auto sm:right-6 sm:left-auto">
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700"
            aria-hidden="true">✓</span>
        <p class="min-w-0 flex-1 wrap-break-words text-sm leading-5">{{ session('success') }}</p>
        <button type="button" @click="show = false"
            class="flex size-7 shrink-0 items-center justify-center rounded-md text-lg leading-none text-emerald-800 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700"
            aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms role="alert" aria-live="assertive"
        class="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-lg border border-red-200 border-l-4 bg-white p-4 text-red-950 shadow-lg sm:inset-x-auto sm:right-6 sm:left-auto">
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-sm font-bold text-red-700"
            aria-hidden="true">!</span>
        <p class="min-w-0 flex-1 wrap-break-words text-sm leading-5">{{ session('error') }}</p>
        <button type="button" @click="show = false"
            class="flex size-7 shrink-0 items-center justify-center rounded-md text-lg leading-none text-red-800 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700"
            aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition.opacity.duration.200ms
        x-init="setTimeout(() => show = false, 7000)" role="alert" aria-live="assertive"
        class="fixed inset-x-4 top-4 z-50 mx-auto flex max-w-md items-start gap-3 rounded-lg border border-amber-200 border-l-4 bg-white p-4 text-amber-950 shadow-lg sm:inset-x-auto sm:right-6 sm:left-auto">
        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700"
            aria-hidden="true">!</span>
        <p class="min-w-0 flex-1 wrap-break-words text-sm leading-5">{{ $errors->first() }}</p>
        <button type="button" @click="show = false"
            class="flex size-7 shrink-0 items-center justify-center rounded-md text-lg leading-none text-amber-800 hover:bg-amber-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-700"
            aria-label="Tutup notifikasi">&times;</button>
    </div>
@endif
