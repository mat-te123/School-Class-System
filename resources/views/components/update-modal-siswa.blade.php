<x-update-modal type="Siswa" subtext="Perbarui atau ubah data siswa">
    <form :action="editUrl" method="POST" class="flex flex-col gap-3">
        @csrf
        @method('PUT')

        <div class="flex flex-col gap-1">
            <label for="fnamalengkap" class="text-sm leading-4 font-semibold">Nama Lengkap</label>
            <input name="nama_lengkap" id="fnamalengkap" type="text" x-model="studentData.nama_lengkap"
                class="border border-black rounded-lg py-1 px-4 text-base">
        </div>

        <div class="flex flex-row gap-2">
            <div class="flex flex-col gap-1 w-full">
                <label for="fnisn" class="text-sm leading-4 font-semibold">NISN</label>
                <input name="nisn" id="fnisn" type="text" x-model="studentData.nisn"
                    class="border border-black rounded-lg py-1 px-4 w-full text-base">
            </div>
            <div class="flex flex-col gap-1 w-full">
                <label for="fnis" class="text-sm leading-4 font-semibold">NIS</label>
                <input name="nis" id="fnis" type="text" x-model="studentData.nis"
                    class="border border-black rounded-lg py-1 px-4 w-full text-base">
            </div>
        </div>

        <div class="flex flex-row gap-2">
            <div class="flex flex-col gap-1 w-full">
                <label for="fkelas" class="text-sm leading-4 font-semibold">Kelas</label>
                <input name="kelas_asal" id="fkelas" type="text" x-model="studentData.kelas_asal"
                    class="border border-black rounded-lg py-1 px-4 w-full text-base">
            </div>
            <div class="flex flex-col gap-1 w-full">
                <label for="fjeniskelamin" class="text-sm leading-4 font-semibold">Jenis Kelamin</label>
                <select name="jenis_kelamin" id="fjeniskelamin" x-model="studentData.jenis_kelamin"
                    class="border border-black rounded-lg py-1 px-4 w-full text-base">
                    <option value="">- Pilih -</option>
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>
        </div>
        @php
            $currentyear = (int) date('Y');
            $startyear = $currentyear - 5;
            $endyear = $currentyear + 5;
        @endphp
        <div class="flex flex-col gap-1">
            <label for="fangkatan" class="text-sm leading-4 font-semibold">Angkatan</label>
            <select name="angkatan" id="fangkatan" x-model="studentData.angkatan"
                class="border border-black rounded-lg py-1 px-4 text-base">
                <option value="">- Pilih angkatan -</option>

                @for ($year = $endyear; $year >= $startyear; $year--)
                    <option value="{{ $year }}/{{ $year + 1 }}">{{ $year }}/{{ $year + 1 }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="flex flex-row justify-end gap-3 mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showupdateModal = false">
                Batal
            </button>
            <button class="bg-blue-500 text-white text-xs rounded-lg py-2 px-4" type="submit">
                Update
            </button>
        </div>
    </form>
</x-update-modal>
