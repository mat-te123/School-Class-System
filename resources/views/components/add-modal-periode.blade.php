<x-add-modal subtext="Menambahkan periode baru">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp

    <form :action="addUrl" method="POST" class="flex flex-col gap-3">
        @csrf
        @method('POST')

        <div class="flex flex-col gap-1">
            <label for="fnama_periode" class="text-sm leading-4 font-semibold">Nama Periode</label>
            <input name="nama_periode" id="fperiode" type="text" required
                class="border border-black rounded-lg py-1 px-4 text-base">
        </div>

        @php
            $currentyear = (int) date('Y');
            $startyear = $currentyear - 5;
            $endyear = $currentyear + 5;
        @endphp
        <div class="flex flex-col gap-1">
            <label for="ftahun_ajaran" class="text-sm leading-4 font-semibold">Tahun Ajaran</label>
            <select name="tahun_ajaran" id="ftahunajaran" class="border border-black rounded-lg py-1 px-4 text-base"
                required>
                <option value="">-- Pilih Tahun Ajaran --</option>

                @for ($year = $endyear; $year >= $startyear; $year--)
                    <option value="{{ $year }}/{{ $year + 1 }}">{{ $year }}/{{ $year + 1 }}
                    </option>
                @endfor
            </select>
        </div>

        <div class="flex flex-row justify-between gap-3">
            <div class="flex flex-col gap-1 w-full">
                <label for="fgelombang" class="text-sm leading-4 font-semibold">Gelombang</label>
                <input name="gelombang" id="ftanggal_buka" type="text" maxlength="20" required
                    placeholder="Cth. utama" class="border border-black rounded-lg py-1 px-4 text-base w-full">
            </div>

            <div class="flex flex-col gap-1 w-full">
                <label for="fmax_pilihan_siswa" class="text-sm leading-4 font-semibold">Max Pilihan Siswa</label>
                <input name="max_pilihan_siswa" id="ftanggal_tutup" type="number" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
            </div>
        </div>

        <div class="flex flex-row justify-between gap-3 w-full">
            <div class="flex flex-col gap-1 w-full">
                <label for="ftanggal_buka" class="text-sm leading-4 font-semibold">Tanggal Buka</label>
                <input name="tanggal_buka" id="ftanggal_buka" type="date" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-fit">
            </div>

            <div class="flex flex-col gap-1 w-12.5">
                <label for="ftanggal_tutup" class="text-sm leading-4 font-semibold">Tanggal Tutup</label>
                <input name="tanggal_tutup" id="ftanggal_tutup" type="date" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-12.5">
            </div>
        </div>


        <div class="flex flex-row justify-end mt-4">
            <button type="submit" class="{{ $btn_primary }}">
                Tambahkan Periode
            </button>
        </div>


</x-add-modal>
