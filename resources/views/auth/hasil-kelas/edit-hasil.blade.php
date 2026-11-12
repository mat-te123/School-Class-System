<x-app-layout title="Detail Hasil Seleksi">
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col border border-b-black px-8 py-6">
            <h1 class="text-4xl font-semibold leading-9">
                Hasil Seleksi >
                <span class="text-2xl lowercase text-gray-500">{{ $hasil->siswa?->nama_lengkap ?? '-' }}</span>
            </h1>
            <span class="text-md font-medium leading-5">
                Detail hasil penempatan siswa
            </span>
        </div>

        <div class="relative flex flex-col gap-4 px-8 py-6">
            <a href="{{ url()->previous() }}" class="back-button">
                Kembali
            </a>

            <div class="flex flex-col gap-4 rounded-lg border border-[#D1D5DB] bg-[#F9FAFB] p-5">
                <div class="flex flex-col gap-2 border-b border-gray-300 pb-3">
                    <h2 class="text-2xl font-semibold leading-6">
                        {{ $hasil->siswa?->nama_lengkap ?? '-' }}
                    </h2>
                    <span class="text-sm text-gray-500">
                        NISN: {{ $hasil->siswa?->nisn ?? '-' }} • NIS: {{ $hasil->siswa?->nis ?? '-' }}
                    </span>
                </div>

                @php
                    $studentData = [
                        'Jenis kelamin' => $hasil->siswa?->jenis_kelamin ?? '-',
                        'Angkatan' => $hasil->siswa?->angkatan ?? '-',
                        'Status' => $hasil->siswa?->is_active ? 'Aktif' : 'Tidak aktif',
                    ];
                @endphp
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($studentData as $label => $value)
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">{{ $label }}</span>
                            <span class="text-base font-semibold">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div
                class="flex flex-col gap-2 rounded-lg border p-5 {{ $hasil->paket_menu_pilihan_id ? 'border-green-300 bg-green-50' : 'border-amber-300 bg-amber-50' }}">
                <span class="text-sm font-medium text-slate-500">Status penempatan</span>
                <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2
                            class="text-2xl font-semibold {{ $hasil->paket_menu_pilihan_id ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $hasil->paket_menu_pilihan_id ? 'Diterima' : 'Belum diterima' }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            {{ $hasil->paketMenuPilihan?->nama_menu ?? 'Belum mendapatkan penempatan kelas.' }}
                        </p>
                    </div>
                    <span
                        class="w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $hasil->paket_menu_pilihan_id ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                        {{ $hasil->paket_menu_pilihan_id ? 'Lolos seleksi' : 'Belum ditempatkan' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @php
                    $assessment = [
                        'Skor penempatan' => $hasil->skor_penempatan ?? '-',
                        'Rata-rata 6 mata pelajaran' => $hasil->rata_6_mapel ?? '-',
                        'Peringkat pada pilihan' => $hasil->rank_pada_pilihan ?? '-',
                        'Pilihan diterima' => $hasil->pilihan_ke_diterima ? 'Pilihan ' . $hasil->pilihan_ke_diterima : '-',
                    ];
                    $process = [
                        'Mekanisme' => $hasil->mekanisme ?? '-',
                        'Tanggal diproses' => $hasil->tanggal_diproses?->format('d M Y H:i') ?? '-',
                        'Diubah manual' => $hasil->is_manual_override ? 'Ya' : 'Tidak',
                        'Diubah oleh' => $hasil->pengubah?->username ?? '-',
                        'Tanggal perubahan' => $hasil->tanggal_perubahan?->format('d M Y H:i') ?? '-',
                    ];
                @endphp

                @foreach (['Penilaian' => $assessment, 'Proses' => $process] as $title => $details)
                    <div class="flex flex-col gap-2 rounded-lg border border-[#D1D5DB] bg-white p-5">
                        <h2 class="text-2xl font-medium">{{ $title }}</h2>
                        @foreach ($details as $label => $value)
                            <div class="flex flex-row justify-between gap-4 border-b border-slate-300 py-3 last:border-b-0">
                                <span class="text-sm text-slate-500">{{ $label }}</span>
                                <span class="text-right text-base font-semibold">{{ $value }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-4 rounded-lg border border-[#D1D5DB] bg-white p-5">
                <div>
                    <h2 class="text-2xl font-medium">Kriteria Kelas</h2>
                    <p class="text-sm text-slate-500">
                        {{ $hasil->paketMenuPilihan?->nama_menu ?? 'Tidak ada kelas yang dipilih' }}
                    </p>
                </div>

                @if ($hasil->paketMenuPilihan?->kriteriaBobots?->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-100 text-slate-700">
                                    <th class="p-3">Mata pelajaran</th>
                                    <th class="p-3">Bobot</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($hasil->paketMenuPilihan->kriteriaBobots as $kriteria)
                                    <tr>
                                        <td class="p-3">{{ $kriteria->mataPelajaran?->nama_mapel ?? '-' }}</td>
                                        <td class="p-3 font-semibold">{{ $kriteria->bobot_persen }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Belum ada kriteria untuk kelas ini.</p>
                @endif
            </div>

            @if ($hasil->catatan_perubahan)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <h2 class="font-semibold text-amber-800">Catatan perubahan</h2>
                    <p class="mt-1 text-sm text-amber-900">{{ $hasil->catatan_perubahan }}</p>
                </div>
            @endif

            @if (is_array($hasil->riwayat_proses) && count($hasil->riwayat_proses))
                <div class="flex flex-col gap-3 rounded-lg border border-[#D1D5DB] bg-white p-5">
                    <h2 class="text-2xl font-medium">Riwayat proses</h2>
                    <ol class="list-decimal space-y-2 pl-5 text-sm text-slate-600">
                        @foreach ($hasil->riwayat_proses as $riwayat)
                            <li>{{ $riwayat }}</li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
