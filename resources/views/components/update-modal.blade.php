@props([
    'id' => 'update-modal',
])

<div x-show="showUpdateModal" x-cloak x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div @click.outside="showUpdateModal = false"
        class="bg-white rounded-xl border border-gray-400 flex flex-col p-6 w-full max-w-md shadow-2xl gap-4">
        <div class="flex flex-col items-center gap-3">
            <div class="bg-blue-200 border border-blue-600 p-2 rounded-xl shadow-lg">
                <img src="{{ asset('Icon/Edit.svg') }}" alt="trash_icon" class="w-8 h-8">
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Edit</h3>
        </div>
        <p class="text-gray-600 text-sm text-center">
            Apakah Anda yakin ingin mengubah data atas nama
            <span class="font-semibold text-gray-900" x-text="studentName"></span>?
        </p>
        <div class="flex flex-row items-center justify-center gap-2 mt-2">
            @csrf

            <button type="button" @click="showUpdateModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors">
                Batal
            </button>
            <a :href="editUrl"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                Ya
            </a>
        </div>
    </div>
</div>
