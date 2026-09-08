@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center justify-between w-fit max-w-[90vw] sm:max-w-lg bg-emerald-100 border border-emerald-400 text-emerald-900 px-4 py-3 rounded-lg mb-4 shadow-md">
        <span class="wrap-break-words">✅ {{ session('success') }}</span>
        <button @click="show = false" class="font-bold text-emerald-900 hover:opacity-75">&times;</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center justify-between w-fit max-w-[90vw] sm:max-w-lg bg-red-100 border border-red-400 text-red-900 px-4 py-3 rounded-lg mb-4 shadow-md">
        <span class="wrap-break-words">⚠️ {{ session('error') }}</span>
        <button @click="show = false" class="font-bold text-red-900 hover:opacity-75">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{ show: true }" x-show="show"
        class="fixed top-4 left-1/2 -translate-x-1/2 z-50 flex items-center justify-between  w-fit max-w-[90vw] sm:max-w-lg bg-amber-100 border border-amber-400 text-amber-900 px-4 py-3 rounded-lg mb-4 shadow-md">
        <span class="wrap-break-words">⚠️ {{ $errors->first() }}</span>
        <button @click="show = false" class="font-bold text-amber-900 hover:opacity-75">&times;</button>
    </div>
@endif
