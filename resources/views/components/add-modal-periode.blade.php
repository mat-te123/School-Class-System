@props(['tahunAjaranSekarang' => ''])

<x-add-modal subtext="Menambahkan periode baru" tahunAjaranSekarang="{{ $tahunAjaranSekarang }}">
    @php
        $btn_primary = 'bg-blue-500 hover:bg-blue-600 text-white text-xs rounded-lg py-2 px-4 transition';
    @endphp

    <form :action="addUrl" method="POST" class="flex flex-col gap-3">
        @csrf
        @method('POST')

        <div class="flex flex-col gap-1">
            <label for="fnama_periode" class="text-sm leading-4 font-semibold">Nama Periode
                <span class="text-red-500"> * </span>
            </label>
            <input name="nama_periode" id="fperiode" type="text" required
                class="border border-black rounded-lg py-1 px-4 text-base">
        </div>

        @php
            $currentyear = (int) date('Y');
        @endphp
        {{-- <div class="flex flex-col gap-1">
            <label for="ftahun_ajaran" class="text-sm leading-4 font-semibold">Tahun Ajaran</label>
            <select name="tahun_ajaran" id="ftahun_ajaran" class="border border-black rounded-lg py-1 px-4 text-base"
                required>
                @for ($year = $currentyear + 2; $year >= $currentyear; $year--)
                    <option @selected($year === $currentyear) value="{{ $year }}/{{ $year + 1 }}">
                        {{ $year }}/{{ $year + 1 }}
                    </option>
                @endfor
            </select>
        </div> --}}
        <div class="flex flex-col gap-1">
            <label for="ftahun_ajaran" class="text-sm leading-4 font-semibold">Tahun Ajaran
                <span class="text-red-500"> * </span>
            </label>
            <select disabled name="tahun_ajaran" id="ftahun_ajaran"
                class="border border-black rounded-lg py-1 px-4 text-base" required>
                <option value="{{ $tahunAjaranSekarang }}" disabled selected>
                    {{ $tahunAjaranSekarang }}
                </option>
            </select>
        </div>



        <div class="flex flex-row justify-between gap-3">
            <div class="flex flex-col gap-1 w-full">
                <label for="fgelombang" class="text-sm leading-4 font-semibold">Gelombang
                    <span class="text-red-500"> * </span>
                </label>
                <select name="gelombang" id="fgelombang" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
                    <option value="" disabled selected>Pilih gelombang</option>
                    <option value="Utama">Utama</option>
                    <option value="Susulan">Susulan</option>
                </select>
            </div>

            <div class="flex flex-col gap-1 w-full">
                <label for="fmax_pilihan_siswa" class="text-sm leading-4 font-semibold">Max Pilihan Siswa
                    <span class="text-red-500"> * </span>
                </label>
                <input name="max_pilihan_siswa" id="ftanggal_tutup" type="number" required min="1" max="5" step="1"
                    oninput="if(this.value !== '') { if(Number(this.value) > 5) this.value = 5; if(Number(this.value) < 1) this.value = 1; }"
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
            </div>
        </div>

        <div class="flex flex-row justify-between gap-3 w-full">
            <div class="flex flex-col gap-1 w-full">
                <label for="ftanggal_buka" class="text-sm leading-4 font-semibold">Tanggal Buka
                    <span class="text-red-500"> * </span>
                </label>
                <input name="tanggal_buka" id="ftanggal_buka" type="date" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
            </div>

            <div class="flex flex-col gap-1 w-full">
                <label for="ftanggal_tutup" class="text-sm leading-4 font-semibold"
                    title="tanggal periode dapat diakses siswa">Tanggal Tutup
                    <span class="text-red-500"> * </span>
                </label>
                <input name="tanggal_tutup" id="ftanggal_tutup" type="date" required
                    class="border border-black rounded-lg py-1 px-4 text-base w-full">
            </div>
        </div>


        <div class="flex flex-row justify-end mt-4">
            <button type="button" class="text-red-600 py-1 px-2 text-sm" @click="showaddmodal = false">
                Batal
            </button>
            <button type="submit" class="{{ $btn_primary }}">
                Tambahkan Periode
            </button>
        </div>
    </form>


</x-add-modal>
