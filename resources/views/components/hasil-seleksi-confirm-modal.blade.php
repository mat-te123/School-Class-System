<div x-show="showConfirmModal" x-cloak
    class="flex flex-col fixed top-0 left-0 w-full h-full bg-black/50 z-50 justify-center items-center"
    style="display: none;">
    <div class="flex flex-col bg-white rounded-lg p-6 gap-4 w-100" @click.away="showConfirmModal = false">
        <h2 class="text-lg font-semibold" x-text="mainTitle">
        </h2>
        <p class="text-sm text-gray-500" x-text="subTitle">
        </p>
        <div class="flex flex-row justify-end gap-2">
            <button type="button" @click="showConfirmModal = false"
                class="text-sm px-4 py-2 text-white bg-red-500 rounded-md hover:bg-red-600">
                Batal
            </button>
            <form method="POST" x-bind:action="ModalUrl">
                @csrf
                <input type="hidden" name="periode_id" value="{{ request('periode_id') }}">
                <button type="submit" class="text-sm px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                    Ya
                </button>
            </form>
        </div>
    </div>


</div>
