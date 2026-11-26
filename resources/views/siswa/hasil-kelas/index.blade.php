<x-app-layout title="Hasil Seleksi" type='siswa'>
    <div class="flex flex-row items-center justify-between border-b border-black px-8 py-6">
        <div class="flex flex-col">
            <h1 class="text-3xl font-semibold leading-9">
                Hasil Penempatan
            </h1>
            <span class="text-sm font-medium leading-5 text-slate-500">
                Lihat hasil seleksi dan penempatan kelas Anda
            </span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 px-8 py-6">
        <a href="{{ route('siswa.dashboard') }}" class="back-button">
            Kembali
        </a>
        <div
            class="col-span-2 flex flex-col gap-2 rounded-lg border p-5 {{ $hasil->pilihan_ke_diterima ? 'border-green-300 bg-green-50' : 'border-amber-300 bg-amber-50' }}">
            <span class="text-sm font-medium text-slate-500">Status penempatan</span>
            <div class="flex flex-row items-center justify-between gap-4">
                <div>
                    <h2
                        class="text-2xl font-semibold {{ $hasil->pilihan_ke_diterima ? 'text-green-700' : 'text-amber-700' }}">
                        {{ $hasil->pilihan_ke_diterima ? 'Diterima' : 'Belum diterima' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        @if ($hasil->pilihan_ke_diterima)
                            Selamat, Anda diterima pada pilihan kelas ke-{{ $hasil->pilihan_ke_diterima }}.
                        @else
                            Anda belum mendapatkan penempatan kelas pada hasil seleksi ini.
                        @endif
                    </p>
                </div>
                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $hasil->pilihan_ke_diterima ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                    {{ $hasil->pilihan_ke_diterima ? 'Lulus seleksi' : 'Belum ditempatkan' }}
                </span>
            </div>
        </div>
        @php
            $Penilaian = [
                'Skor Penempatan' => $hasil->skor_penempatan ?? '-',
                'Rata-rata 6 Mata Pelajaran' => $hasil->rata_6_mapel ?? '-',
                'Peringkat pada Pilihan' => $hasil->rank_pada_pilihan ?? '-',
            ];
            $Proses = [
                'Kelas diterima' => $hasil->paketMenuPilihan?->nama_menu ?? '-',
                'Mekanisme' => $hasil->mekanisme ?? '-',
                'Tanggal diproses' => $hasil->tanggal_diproses?->format('d M Y H:i') ?? '-',
            ];
            $coldata = [
                'Penilaian' => $Penilaian,
                'Proses' => $Proses,
            ];
        @endphp
        @foreach ($coldata as $colname => $data)
            <div class="col-span-1">
                <h2 class="font-medium text-2xl">
                    {{ $colname }}
                </h2>
                @foreach ($data as $key => $value)
                    <div class="flex flex-row justify-between py-3 border-b border-slate-300 mr-4">
                        <p class="text-sm text-slate-500">{{ $key }}</p>
                        <span class="text-base font-semibold">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>

</x-app-layout>
