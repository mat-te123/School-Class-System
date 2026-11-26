@props(['isSiswaLoggedIn' => false])
<div {{ $attributes->merge([
    'class' => 'h-full flex flex-col bg-white p-6 border-t border-slate-200 gap-4',
]) }}>
    @if ($PilihanSiswa->isEmpty())
        <div class="bg-gray-50 border border-gray-300 rounded-lg flex flex-col items-center justify-center py-12 px-2  ">
            <h3 class="mainTitle text-lg">
                Data pilihan tidak tersedia
            </h3>
            <p class="subTitle text-sm">
                Siswa belum melakukan pemilihan kelas
            </p>

        </div>
    @else
        <div class="flex flex-col">
            <h2 class="mainTitle text-lg">
                Hasil Pilihan Siswa
            </h2>
            <span class="subTitle">
                Hasil akhir pilihan siswa
            </span>
        </div>
        @if ($HasilPilihanSiswa->isNotEmpty())
            <div class="grid grid-cols-2 gap-4">
                @foreach ($HasilPilihanSiswa as $hasil)
                    <div
                        class="flex flex-row w-full bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4 items-center">
                        <span
                            class="bg-black p-4 w-8 h-8 text-white rounded-xl flex items-center justify-center text-lg">
                            {{ $hasil->pilihan_ke_diterima }}
                        </span>
                        <div>
                            <h2>
                                {{ $hasil->paketMenuPilihan?->nama_menu ?? '-' }}
                            </h2>
                            <span class="text-sm text-gray-500">
                                {{ $hasil->paketMenuPilihan?->rumpun ?? '-' }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div
                class="h-40 border border-gray-300 rounded-lg bg-gray-50 justify-center items-center flex text-gray-400">
                <p class="text-sm">
                    Hasil seleksi belum diumumkan oleh guru
                </p>
            </div>
        @endif
        <div class="flex flex-row justify-between items-end">
            <div class="flex flex-col">
                <h2 class="mainTitle text-lg">
                    Daftar Pilihan Siswa
                </h2>
                <span class="subTitle">
                    Daftar pilihan siswa yang telah diinputkan
                </span>

            </div>
            @if (!$isSiswaLoggedIn)
                <a href="{{ route('siswa.pilih-kelas.index') }}" class="primary-button w-fit whitespace-nowrap h-fit">
                    Perbarui pilihan kelas
                </a>
            @endif

        </div>
        <div class="grid grid-cols-2 gap-4">
            @foreach ($PilihanSiswa as $pilihan)
                <div
                    class="flex flex-row w-full bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4 items-center">
                    <span class="bg-black p-4 w-8 h-8 text-white rounded-xl flex items-center justify-center text-lg">
                        {{ $pilihan->urutan_pilihan }}
                    </span>
                    <div>
                        <h2>
                            {{ $pilihan->paketMenuPilihan?->nama_menu ?? '-' }}
                        </h2>
                        <span class="text-sm text-gray-500">
                            {{ $pilihan->paketMenuPilihan?->rumpun ?? '-' }}
                        </span>
                    </div>
                </div>
            @endforeach

        </div>



    @endif


</div>
