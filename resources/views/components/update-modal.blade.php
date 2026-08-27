@props([
    'id' => 'update-modal',
    'show' => 'showupdateModal',
    'type' => '',
    'subtext' => '',
])

<div x-show="{{ $show }}" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div @click.outside="{{ $show }} = false"
        class="bg-white rounded-xl border border-gray-400 flex flex-col p-8 w-full max-w-sm shadow-2xl gap-4">
        <div class="flex flex-row w-full justify-between items-center">
            <div class="flex flex-col gap-0">
                <h3 class="text-2xl leading-8 font-bold">
                    Edit Data {{ $type }}
                </h3>
                <span class="text-xs leading-4">
                    {{ $subtext }}
                </span>
            </div>
            <span @click="{{ $show }} = false" class="cursor-pointer">
                X
            </span>

        </div>
        <div>
            {{ $slot }}
        </div>
    </div>
</div>
