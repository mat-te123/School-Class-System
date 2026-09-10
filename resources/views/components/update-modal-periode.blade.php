<x-update-modal subtext="Mengubah data periode" type="Periode">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp

    <form :action="editUrl" method="POST" class="flex flex-col gap-3" novalidate
        @submit.prevent="if (confirm('Apakah Anda yakin ingin mengubah data periode ini?')) { $el.submit(); }">
        @csrf
        @method('PUT')



        <div class="flex flex-col gap-1">
            <label for="fnama_periode_edit" class="text-sm leading-4 font-semibold">Nama Periode</label>
            <input name="nama_periode" id="fnama_periode_edit" type="text" x-model="periodeData.nama_periode"
                class="border border-black rounded-lg py-1 px-4 text-base">
        </div>

        @php
            $currentyear = (int) date('Y');
            $startyear = $currentyear - 5;
            $endyear = $currentyear + 5;
        @endphp
        <div class="flex flex-col gap-1">
            <label for="ftahun_ajaran_edit" class="text-sm leading-4 font-semibold">Tahun Ajaran</label>
            <select name="tahun_ajaran" id="ftahun_ajaran_edit"
                class="border border-black rounded-lg py-1 px-4 text-base" x-model="periodeData.tahun_ajaran">
                <option value="">-- Pilih Tahun Ajaran --</option>

                @for ($year = $endyear; $year >= $startyear; $year--)
                    <option value="{{ $year }}/{{ $year + 1 }}">{{ $year }}/{{ $year + 1 }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="flex flex-row justify-between gap-3">
            <div class="flex flex-col gap-1 w-full">
                <label for="fgelombang_edit" class="text-sm leading-4 font-semibold">Gelombang</label>
                <input name="gelombang" id="fgelombang_edit" type="text" maxlength="20" placeholder="Cth. utama"
                    class="border border-black rounded-lg py-1 px-4 text-base w-full" x-model="periodeData.gelombang">
            </div>

            <div class="flex flex-col gap-1 w-full">
                <label for="fmax_pilihan_siswa_edit" class="text-sm leading-4 font-semibold">Max Pilihan Siswa</label>
                <input name="max_pilihan_siswa" id="fmax_pilihan_siswa_edit" type="number"
                    class="border border-black rounded-lg py-1 px-4 text-base w-full"
                    x-model="periodeData.max_pilihan_siswa">
            </div>
        </div>

        <div class="flex flex-row justify-between gap-3 w-full">
            <div class="flex flex-col gap-1 w-full">
                <label for="ftanggal_buka_edit" class="text-sm leading-4 font-semibold">Tanggal Buka</label>
                <input name="tanggal_buka" id="ftanggal_buka_edit" type="date"
                    class="border border-black rounded-lg py-1 px-4 text-base w-fit" x-model="periodeData.tanggal_buka">
            </div>

            <div class="flex flex-col gap-1 w-12.5">
                <label for="ftanggal_tutup_edit" class="text-sm leading-4 font-semibold">Tanggal Tutup</label>
                <input name="tanggal_tutup" id="ftanggal_tutup_edit" type="date"
                    class="border border-black rounded-lg py-1 px-4 text-base w-12.5"
                    x-model="periodeData.tanggal_tutup">
            </div>
        </div>


        <div class="flex flex-col gap-1">
            <label for="fis_active_edit" class="text-sm leading-4 font-semibold">Status Periode</label>
            <select name="is_active" id="fis_active_edit" class="border border-black rounded-lg py-1 px-4 text-base"
                x-model="periodeData.is_active">
                <option value="1">Aktif</option>
                <option value="0">Tidak Aktif</option>
            </select>
        </div>


        <div class="flex flex-row justify-end mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showupdateModal = false">
                Batal
            </button>
            <button type="submit" class="{{ $btn_primary }}">
                Edit Periode
            </button>
        </div>

    </form>

</x-update-modal>
