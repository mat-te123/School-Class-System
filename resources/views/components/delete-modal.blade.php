@props([
    'id' => 'delete-modal',
])

<div 
    x-show="showDeleteModal" 
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
>
    <div 
        @click.outside="showDeleteModal = false"
        class="bg-white rounded-xl border border-gray-400 flex flex-col p-6 w-full max-w-md shadow-2xl gap-4"
    >
        <div class="flex flex-col items-center gap-3">
            <div class="bg-red-200 border border-red-600 p-2 rounded-xl shadow-lg">
                <img src="{{ asset('Icon/Trash.svg') }}" alt="trash_icon" class="w-8 h-8">
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Konfirmasi Hapus</h3>
        </div>

        <p class="text-gray-600 text-sm text-center">
            Apakah Anda yakin ingin menghapus data atas nama 
            <span class="font-semibold text-gray-900" x-text="studentName"></span>?
        </p>

        <form :action="deleteUrl" method="POST" class="flex flex-row items-center justify-center gap-2 mt-2">
            @csrf
            @method('DELETE')

            <button 
                type="button" 
                @click="showDeleteModal = false"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors"
            >
                Batal
            </button>
            <button 
                type="submit"
                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors shadow-sm"
            >
                Hapus
            </button>
        </form>
    </div>
</div>