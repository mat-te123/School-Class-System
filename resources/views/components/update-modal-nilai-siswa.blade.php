<x-update-modal show="showNilaiUpdateModal" type="Nilai Siswa" subtext="Perbaiki nilai mata pelajaran siswa">
    <form :action="nilaieditUrl" method="POST" class="flex flex-col gap-3">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-1">
            <label class="text-sm leading-4 font-semibold">Mata Pelajaran</label>
            <span class="rounded-lg border border-gray-300 bg-gray-50 px-4 py-2 text-base"
                x-text="nilaiData.mata_pelajaran?.nama_mapel ?? '-'"></span>
        </div>

        <div class="flex flex-col gap-1">
            <label for="nilai-angka" class="text-sm leading-4 font-semibold">Nilai</label>
            <input id="nilai-angka" name="nilai_angka" type="number" min="0" max="100" step="0.01"
                x-model="nilaiData.nilai_angka" class="rounded-lg border border-black px-4 py-2 text-base" required>
        </div>

        <div class="mt-4 flex flex-row justify-end gap-3">
            <button type="button" class="negative-primary-button" @click="showNilaiUpdateModal = false">
                Batal
            </button>
            <button class="primary-button" type="submit">
                Update
            </button>
        </div>
    </form>
</x-update-modal>
