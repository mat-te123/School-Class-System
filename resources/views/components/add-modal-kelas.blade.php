<x-add-modal subtext="Menambahkan periode baru" type="Periode" show="showaddmodal">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp

    <form :action="addUrl" method="POST" class="flex flex-col gap-3" novalidate
        @submit.prevent="if (confirm('Apakah Anda yakin ingin menambahkan data Kelas ini?')) { $el.submit(); }">
        @csrf
        @method('POST')

        {{-- Nama Kelas --}}
        <div class="flex flex-col gap-1">
            <label for="fnama_menu" class="text-sm leading-4 font-semibold">Nama Kelas</label>
            <input name="nama_menu" id="fnama_menu" type="text" required
                class="border border-black rounded-lg py-1 px-4 text-base w-full">
        </div>

        {{-- Rumpun & Kuota --}}
        <div class="flex flex-row gap-3 w-full">
            <div class="flex flex-col gap-1 w-1/2">
                <label for="frumpun" class="text-sm leading-4 font-semibold">Rumpun</label>
                <select name="rumpun" id="frumpun" x-model="Data['rumpun']" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
                    <option value="eksakta">Eksakta</option>
                    <option value="sosial">Sosial</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 w-1/2">
                <label for="fkuota_kapasitas" class="text-sm leading-4 font-semibold">Kuota Kapasitas</label>
                <input name="kuota_kapasitas" id="fkuota_kapasitas" type="number" 
                    class="border border-black rounded-lg py-1 px-4 text-base w-full" min="1" value="32">
            </div>
        </div>

        {{-- Status --}}
        <div class="flex flex-col gap-1">
            <label for="is_active" class="text-sm leading-4 font-semibold">Status</label>
            <select id="is_active" name="is_active" x-model="Data['is_active']"
                class="border border-black rounded-lg py-1 px-4 text-base w-full">
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>

        {{-- Form Actions --}}
        <div class="flex flex-row justify-end gap-3 mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showaddmodal = false">
                Batal
            </button>
            <button type="submit" class="{{ $btn_primary }}">
                Tambah Kelas
            </button>
        </div>
    </form>
</x-add-modal>
