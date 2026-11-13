<x-app-layout>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-xl font-semibold">Laporan Hasil Penjurusan</h1>
            <div class="flex gap-2">
                <a class="primary-button"
                    href="{{ route('admin.laporan.export.hasil-penjurusan', array_merge(request()->query(), ['format' => 'csv'])) }}">
                    Download CSV
                </a>
                <a class="primary-button"
                    href="{{ route('admin.laporan.export.hasil-penjurusan', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                    Download PDF
                </a>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">NISN</th>
                        <th class="p-3">Nama Lengkap</th>
                        <th class="p-3">Kelas Asal</th>
                        <th class="p-3">Paket Penempatan</th>
                        <th class="p-3">Skor</th>
                        <th class="p-3">Rata-rata 6 Mapel</th>
                        <th class="p-3">Mekanisme</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($results as $row)
                        <tr class="border-t border-gray-200">
                            <td class="p-3">{{ $row->siswa?->nisn ?? '-' }}</td>
                            <td class="p-3">{{ $row->siswa?->nama_lengkap ?? '-' }}</td>
                            <td class="p-3">{{ $row->siswa?->kelasAsalRelation?->nama_kelas ?? '-' }}</td>
                            <td class="p-3">{{ $row->paketMenuPilihan?->nama_menu ?? '-' }}</td>
                            <td class="p-3">{{ $row->skor_penempatan ?? '-' }}</td>
                            <td class="p-3">{{ $row->rata_6_mapel ?? '-' }}</td>
                            <td class="p-3">{{ $row->mekanisme ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="p-3" colspan="7">Tidak ada hasil penjurusan untuk filter ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $results->withQueryString()->links() }}
    </div>
</x-app-layout>
