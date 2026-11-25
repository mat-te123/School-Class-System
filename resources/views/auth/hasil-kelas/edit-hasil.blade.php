<x-app-layout title="Detail Hasil Seleksi">
    <div class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col border border-b-black px-8 py-6">
            <h1 class="mainTitle">
                Hasil Seleksi >
                <span class="text-2xl lowercase text-gray-500">{{ $hasil->siswa?->nama_lengkap ?? '-' }}</span>
            </h1>
            <span class="subTitle">
                Tinjau pilihan kelas dan nilai siswa sebelum mengubah hasil penempatan
            </span>
        </div>

        <div class="relative flex flex-col gap-4 px-8 py-6">
            <a href="{{ route('admin.hasil-penjurusan.index', ['periode_id' => $hasil->periode_pendaftaran_id]) }}"
                class="back-button">
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
                        'Kelas hasil penempatan' => $hasil->paketMenuPilihan?->nama_menu ?? '-',
                    ];
                @endphp
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($studentData as $label => $value)
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">{{ $label }}</span>
                            <span class="text-base font-semibold">{{ $value }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @php
                $pendaftaranPilihan = $hasil->siswa?->pendaftaranPilihan ?? collect();
                $pilihanKelas = $pendaftaranPilihan
                    ->flatMap(fn($pendaftaran) => $pendaftaran->detailPendaftaran)
                    ->filter(fn($pilihan) => $pilihan->paketMenuPilihan)
                    ->unique('paket_menu_pilihan_id')
                    ->values();
                $nilaiLeger = $hasil->siswa?->nilaiLegerSiswa ?? collect();
            @endphp

            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="mainTitle text-xl">Pilihan Kelas Sebelumnya</h2>
                    <span class="subTitle">Semua kelas yang pernah dipilih siswa pada setiap pendaftaran</span>
                </div>

                @forelse ($pendaftaranPilihan as $pendaftaran)
                    <div class="flex flex-col gap-3 rounded-lg border border-[#D1D5DB] bg-white p-5">
                        <div class="flex flex-col gap-1 border-b border-slate-200 pb-3">
                            <h3 class="text-lg font-semibold">
                                {{ $pendaftaran->periodePendaftaran?->nama_periode ?? 'Periode pendaftaran' }}
                            </h3>
                            <span class="text-sm text-slate-500">
                                Tahun ajaran {{ $pendaftaran->periodePendaftaran?->tahun_ajaran ?? '-' }}
                                • Dikirim {{ $pendaftaran->tanggal_submit?->format('d M Y H:i') ?? '-' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                            @forelse ($pendaftaran->detailPendaftaran as $pilihan)
                                <div class="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <span
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-black text-lg text-white">
                                        {{ $pilihan->urutan_pilihan }}
                                    </span>
                                    <div>
                                        <p class="font-semibold">
                                            {{ $pilihan->paketMenuPilihan?->nama_menu ?? '-' }}
                                        </p>
                                        <p class="text-sm text-slate-500">
                                            {{ $pilihan->paketMenuPilihan?->rumpun ?? '-' }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500">Tidak ada pilihan kelas pada pendaftaran ini.</p>
                            @endforelse
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                        Siswa belum memiliki riwayat pilihan kelas.
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col gap-4">
                <div>
                    <h2 class="mainTitle text-xl">Nilai Siswa</h2>
                    <span class="subTitle">Nilai yang digunakan sebagai pertimbangan penempatan</span>
                </div>

                @forelse ($nilaiLeger as $leger)
                    <div class="flex flex-col gap-3 rounded-lg border border-[#D1D5DB] bg-white p-5">
                        <div class="flex flex-col gap-1 border-b border-slate-200 pb-3">
                            <h3 class="text-lg font-semibold">
                                {{ $leger->tahun_ajaran }} • Semester {{ $leger->semester }}
                            </h3>
                            <span class="text-sm text-slate-500">
                                Rata-rata 6 mata pelajaran:
                                <strong>{{ $leger->rata_6_mapel ?? '-' }}</strong>
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr
                                        class="bg-[#D1D5DB] font-semibold text-slate-700 first:[&_th]:rounded-l-xl last:[&_th]:rounded-r-xl">
                                        <th class="p-3">No.</th>
                                        <th class="p-3">Mata Pelajaran</th>
                                        <th class="p-3">Nilai</th>
                                        <th class="p-3">Predikat</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse ($leger->details as $index => $detail)
                                        <tr class="hover:bg-slate-50">
                                            <td class="p-3 font-medium">{{ $index + 1 }}</td>
                                            <td class="p-3">
                                                {{ $detail->mataPelajaran?->nama_mapel ?? '-' }}
                                            </td>
                                            <td class="p-3 font-semibold">{{ $detail->nilai_angka ?? '-' }}</td>
                                            <td class="p-3">{{ $detail->predikat ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="p-3 text-center text-slate-500">
                                                Belum ada rincian nilai.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">
                        Data nilai siswa belum tersedia.
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col gap-4 rounded-lg border border-[#D1D5DB] bg-white p-5">
                <div>
                    <h2 class="mainTitle text-xl">Ubah Hasil Penempatan</h2>
                    <span class="subTitle">Pilih salah satu kelas yang pernah dipilih siswa dan isi alasannya</span>
                </div>

                @if ($pilihanKelas->isNotEmpty())
                    <form method="POST" action="{{ route('admin.hasil-penjurusan.override', $hasil->id) }}"
                        class="flex flex-col gap-4">
                        @csrf
                        @method('PUT')

                        <label class="flex flex-col gap-1 text-sm font-medium">
                            Kelas tujuan
                            <select name="paket_menu_pilihan_id" class="select-primary w-full" required>
                                <option value="" disabled selected>Pilih kelas</option>
                                @foreach ($pilihanKelas as $pilihan)
                                    <option value="{{ $pilihan->paket_menu_pilihan_id }}" @selected($hasil->paket_menu_pilihan_id === $pilihan->paket_menu_pilihan_id)>
                                        {{ $pilihan->paketMenuPilihan->nama_menu }}
                                        ({{ $pilihan->paketMenuPilihan->rumpun }})
                                    </option>
                                @endforeach
                            </select>
                        </label>

                        <label class="flex flex-col gap-1 text-sm font-medium">
                            Alasan perubahan
                            <textarea name="catatan_perubahan" rows="3" maxlength="1000" required
                                class="w-full rounded-lg border border-gray-400 px-4 py-2 text-sm"
                                placeholder="Jelaskan alasan perubahan hasil penempatan"></textarea>
                        </label>

                        <button type="submit" class="primary-button w-fit">
                            Simpan Perubahan
                        </button>
                    </form>
                @else
                    <p class="text-sm text-slate-500">Tidak ada pilihan kelas yang dapat digunakan.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
