<x-update-modal subtext="Mengubah data mata pelajaran ini ?" type="Mata Pelajaran">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp

    <form :action="updateUrl" method="POST" class="flex flex-col gap-3" novalidate>
        @csrf
        @method('PUT')

        {{-- nama mata pelajaran disabled --}}
        <div>
            <label for="fnama_mapel_edit" class="text-sm leading-4 font-semibold">Nama Mata Pelajaran</label>
            <input name="nama_mapel" id="fnama_mapel_edit" type="text" x-model="item.master_mata_pelajaran.nama_mapel"
                class="border border-black rounded-lg py-1 px-4 text-base w-full" disabled>
        </div>
        {{-- bobot persen --}}
        <div>
            <label for="fbobot_persen_edit" class="text-sm leading-4 font-semibold">Bobot Persen</label>
            <input name="bobot_persen" id="fbobot_persen_edit" type="number" x-model="item.bobot_persen"
                class="border border-black rounded-lg py-1 px-4 text-base w-full" required>
        </div>


        {{-- Form Actions --}}
        <div class="flex flex-row justify-end gap-3 mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showupdateModal = false">
                Batal
            </button>
            <button type="submit" class="{{ $btn_primary }}">
                Edit Kelas
            </button>
        </div>
    </form>
</x-update-modal>
