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

        <div class="flex flex-row items-center gap-4">
            <div
                class="cursor-pointer rounded-lg border border-slate-200 bg-slate-50 p-2 transition-all duration-300 ease-in-out hover:bg-slate-200">
                <img src="{{ asset('Icon/Bell_light.svg') }}" alt="notification icon" class="h-6 w-6" />
            </div>
            @php
                $siswa = auth('siswa')->user();
                $initials = collect(explode(' ', $siswa?->nama_lengkap ?? 'Siswa'))
                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                    ->join('');
            @endphp
            <div
                class="flex flex-row items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 transition-all duration-300 ease-in-out hover:bg-slate-200">
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-full bg-black p-1 text-sm font-bold leading-4 text-white">
                    {{ $initials }}
                </div>
                <span class="text-xs font-semibold leading-4">
                    {{ $siswa?->nama_lengkap ?? 'Siswa' }}
                </span>
            </div>
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

        {{-- <div class="flex flex-col gap-4 rounded-lg border border-[#D1D5DB] bg-[#F9FAFB] p-5">
            <div class="border-b border-gray-300 pb-3">
                <h2 class="text-xl font-semibold leading-6">Ringkasan Hasil Seleksi</h2>
                <p class="mt-1 text-sm font-medium leading-5 text-slate-500">
                    Detail hasil penempatan kelas Anda.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @php
                    $details = [
                        'Kelas diterima' => $hasil->paketMenuPilihan?->nama_menu ?? '-',
                        'Mekanisme' => $hasil->mekanisme ?? '-',
                        'Skor penempatan' => $hasil->skor_penempatan ?? '-',
                        'Rata-rata 6 mata pelajaran' => $hasil->rata_6_mapel ?? '-',
                        'Peringkat pada pilihan' => $hasil->rank_pada_pilihan ?? '-',
                        'Tanggal diproses' => $hasil->tanggal_diproses?->format('d M Y H:i') ?? '-',
                    ];
                @endphp
                @foreach ($details as $label => $value)
                    <div class="flex flex-col gap-1">
                        <span class="text-sm text-slate-500">{{ $label }}</span>
                        <span class="text-base font-semibold">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </div> --}}
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
