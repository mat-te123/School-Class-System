@props(['paketMenuId' => ''])

<x-add-modal subtext="Menambahkan mata pelajaran baru" type="Mata Pelajaran">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp
    <form action="{{ route('kriteria-bobot.store') }}" method="POST" class="flex flex-col gap-3" novalidate
        x-data="{
            availableMataPelajaran: [],
            async init() {
                try {
                    const response = await fetch('{{ route('master-mapel.index') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const result = await response.json();
                    this.availableMataPelajaran = result.data;
                } catch (error) {
                    console.error('Error fetching available mata pelajaran:', error);
                }
            }
        }" x-effect="console.log('Available Mata Pelajaran:', availableMataPelajaran)">
        @csrf
        @method('POST')
        <input type="hidden" name="paket_menu_pilihan_id" value="{{ $paketMenuId }}">

        {{-- Nama Kelas --}}
        <div class="flex flex-col gap-1">
            <label for="fmaster_mata_pelajaran_id" class="text-sm leading-4 font-semibold">Nama Kelas</label>
            <select name="master_mata_pelajaran_id" id="fmaster_mata_pelajaran_id"
                class="border border-black rounded-lg py-1 px-4 text-base w-full" required>
                <option value="" disabled selected>Pilih Mata Pelajaran</option>
                <template x-for="mataPelajaran in availableMataPelajaran" :key="mataPelajaran.id">
                    <option :value="mataPelajaran.id" x-text="mataPelajaran.nama_mapel"></option>
                </template>
            </select>
        </div>

        {{-- Bobot --}}
        <div class="flex flex-col gap-1">
            <label for="fbobot_persen" class="text-sm leading-4 font-semibold">Bobot Persen</label>
            <input name="bobot_persen" id="fbobot_persen" type="number" min="0" max="100"
                class="border border-black rounded-lg py-1 px-4 text-base w-full" required>
        </div>

        {{-- Form Actions --}}
        <div class="flex flex-row justify-end gap-3 mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showaddmodal = false">
                Batal
            </button>
            <button type="submit" class="{{ $btn_primary }}">
                Tambah Mata Pelajaran
            </button>
        </div>
    </form>


</x-add-modal>
