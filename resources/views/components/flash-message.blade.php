@if (session('success'))
    <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)"
        class="flex items-center justify-between bg-emerald-100 border border-emerald-400 text-emerald-900 px-4 py-3 rounded-lg mb-4">
        <span>✅ {{ session('success') }}</span>
        <button @click="show = false" class="font-bold text-emerald-900 hover:opacity-75">&times;</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }" x-show="show"
        class="flex items-center justify-between bg-red-100 border border-red-400 text-red-900 px-4 py-3 rounded-lg mb-4">
        <span>⚠️ {{ session('error') }}</span>
        <button @click="show = false" class="font-bold text-red-900 hover:opacity-75">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{ show: true }" x-show="show"
        class="flex items-center justify-between bg-amber-100 border border-amber-400 text-amber-900 px-4 py-3 rounded-lg mb-4">
        <span>⚠️ {{ $errors->first() }}</span>
        <button @click="show = false" class="font-bold text-amber-900 hover:opacity-75">&times;</button>
    </div>
@endif
