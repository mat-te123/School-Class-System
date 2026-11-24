@props(['isSiswaLoggedIn' => false])
<div {{ $attributes->merge([
    'class' => 'h-full flex flex-col bg-white p-6 border-t border-slate-200 gap-4',
]) }}>
    @if ($PilihanSiswa->isEmpty())
        <div class="flex flex-col">
            <img src="{{ asset('Icon/Folder_del.png') }}" alt="empty state" class="w-32 h-32 mx-auto" />
            <h2 class="text-lg font-semibold leading-6 text-center">
                Belum ada data pilihan siswa
            </h2>
            <span class="text-sm text-gray-500 text-center">
                Siswa belum memilih paket menu
            </span>

        </div>
    @else
        @if ($HasilPilihanSiswa->isNotEmpty())
            <div class="flex flex-col">
                <h2 class="mainTitle text-lg">
                    Hasil Pilihan Siswa
                </h2>
                <span class="subTitle">
                    Hasil pilihan siswa yang telah diinputkan
                </span>
            </div>
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
        @endif
        <div class="flex flex-row justify-between items-baseline">
            <div class="flex flex-col">
                <h2 class="mainTitle text-lg">
                    Daftar Pilihan Siswa
                </h2>
                <span class="subTitle">
                    Daftar pilihan siswa yang telah diinputkan
                </span>

            </div>
            @if (!$isSiswaLoggedIn)
                <a href="{{ route('siswa.pilih-kelas.index') }}"
                    class="primary-button w-fit whitespace-nowrap h-fit self-baseline">
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
