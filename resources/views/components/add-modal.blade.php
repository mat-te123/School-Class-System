@props([
    'id' => 'add-modal',
    'show' => 'showaddmodal',
    'type' => '',
    'subtext' => '',
])

<div x-show="{{ $show }}" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4"
    @keydown.escape.window="{{ $show }} = false">
    <div @click.outside="{{ $show }} = false" @click.stop role="dialog" aria-modal="true"
        class="flex max-h-[calc(100vh-2rem)] w-full max-w-lg flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-5">
            <div class="flex flex-col gap-1">
                <h3 class="text-xl font-semibold leading-7 text-gray-900">
                    @if ($type == '')
                        Tambahkan Data
                    @else
                        Tambah {{ $type }}
                    @endif
                </h3>
                <span class="text-sm leading-5 text-gray-500">
                    {{ $subtext }}
                </span>
            </div>
            <button type="button" @click="{{ $show }} = false"
                class="flex size-9 shrink-0 items-center justify-center rounded-lg text-xl leading-none text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-700"
                aria-label="Tutup modal">
                &times;
            </button>
        </div>
        <div class="flex flex-col gap-5 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>
    </div>
</div>
