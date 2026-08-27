{{-- resources/views/components/modal-inspect.blade.php --}}
@props([
    'data' => '',
    'show' => 'showinspect',
])

<div 
    x-show="{{ $show }}" 
    x-cloak 
    @keydown.escape.window="{{ $show }} = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
>
    {{-- Modal Box --}}
    <div 
        @click.outside="{{ $show }} = false" 
        class="w-full max-w-4xl bg-gray-900 border border-gray-700 rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 bg-gray-800 border-b border-gray-700">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-sm font-semibold tracking-wide text-gray-200 uppercase">
                    Inspect Response
                </span>
            </div>
            <button 
                @click="{{ $show }} = false" 
                class="text-gray-400 hover:text-white transition-colors text-xl leading-none"
            >
                &times;
            </button>
        </div>

        {{-- Content Body --}}
        <div class="p-6 overflow-y-auto font-mono text-xs text-emerald-400 bg-gray-950 leading-relaxed whitespace-pre-wrap break-all">
            @if(is_array($data) || is_object($data))
                <pre><code>{{ json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            @else
                <pre><code>{{ $data }}</code></pre>
            @endif
        </div>

        {{-- Footer --}}
        <div class="flex justify-end px-6 py-3 bg-gray-800 border-t border-gray-700">
            <button 
                @click="{{ $show }} = false" 
                class="px-4 py-1.5 text-xs font-semibold text-gray-300 bg-gray-700 hover:bg-gray-600 rounded-md transition"
            >
                Tutup
            </button>
        </div>
    </div>
</div>