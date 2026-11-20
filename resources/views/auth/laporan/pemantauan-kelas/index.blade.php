<x-app-layout>
    <div x-data="{ showDownloadModal: false, downloadFormat: 'pdf' }"
        @keydown.escape.window="showDownloadModal = false">

        <div class="flex flex-col border-b border-black px-4 py-6 sm:px-8">
            <h1 class="mainTitle">Minat Siswa</h1>
            <span class="subTitle">Laporan pilihan kelas yang dipilih siswa pada periode penjurusan</span>
        </div>

        <div class="flex flex-col gap-6 px-4 py-6 sm:px-8">
            <section class="flex flex-row items-end justify-between">
                <div class="flex flex-col">
                    <h2 class="mainTitle text-lg">Data Pilihan Siswa</h2>
                    <p class="subTitle text-sm">
                        Pantau pilihan kelas siswa dan gunakan filter untuk menampilkan data sesuai kebutuhan.
                    </p>
                </div>
                <button type="button" class="negative-primary-button h-fit w-fit whitespace-nowrap"
                    @click="showDownloadModal = true">
                    Download data minat
                </button>
            </section>

            <form method="GET" action="{{ route('admin.laporan.minat-siswa') }}"
                class="flex w-full flex-row items-center gap-3 rounded-lg border border-gray-400 bg-gray-100 p-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama atau NISN"
                    class="w-full rounded-lg border border-gray-400 bg-[#F9FAFB] px-4 py-2 text-sm">

                <select class="select-primary text-sm" name="periode_id" onchange="this.form.submit()">
                    @foreach ($periodeList as $periode)
                        <option value="{{ $periode->id }}" @selected(request('periode_id', $periodeList->first()?->id) == $periode->id)>
                            {{ $periode->nama_periode }}
                        </option>
                    @endforeach
                </select>

                <select class="select-primary text-sm" name="paket_id" onchange="this.form.submit()">
                    <option value="">Semua paket</option>
                    @foreach ($periodePaketList as $paket)
                        <option value="{{ $paket->id }}" @selected(request('paket_id') == $paket->id)>
                            {{ $paket->nama_menu }}
                        </option>
                    @endforeach
                </select>

                <select class="select-primary text-sm" name="kelas_asal_id" onchange="this.form.submit()">
                    <option value="">Semua kelas asal</option>
                    @foreach ($kelasAsalList as $kelas)
                        <option value="{{ $kelas->id }}" @selected(request('kelas_asal_id') == $kelas->id)>
                            {{ $kelas->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Lengkap</th>
                            <th>Kelas Asal</th>
                            @for ($i = 1; $i <= (int) ($selectedPeriode?->max_pilihan_siswa ?? 0); $i++)
                                <th>Pilihan {{ $i }}</th>
                            @endfor
                            <th>Tanggal Submit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($results as $row)
                            @php
                                $pilihan = [];
                                foreach ($row->detailPendaftaran as $detail) {
                                    $pilihan[$detail->urutan_pilihan] = $detail->paketMenuPilihan?->nama_menu;
                                }
                            @endphp
                            <tr>
                                <td>{{ $row->siswa?->nisn ?? '-' }}</td>
                                <td>{{ $row->siswa?->nama_lengkap ?? '-' }}</td>
                                <td>{{ $row->siswa?->kelasAsalRelation?->nama_kelas ?? '-' }}</td>
                                @for ($i = 1; $i <= (int) ($selectedPeriode?->max_pilihan_siswa ?? 0); $i++)
                                    <td>{{ $pilihan[$i] ?? '-' }}</td>
                                @endfor
                                <td>{{ $row->tanggal_submit?->format('d/m/Y H:i') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + (int) ($selectedPeriode?->max_pilihan_siswa ?? 0) }}">
                                    @if (request()->hasAny(['search', 'periode_id', 'paket_id', 'kelas_asal_id']))
                                        Tidak ada hasil untuk filter ini.
                                    @else
                                        Tidak ada data minat siswa.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($results->hasPages())
                <div class="flex items-center justify-between rounded-lg bg-gray-100 px-4 py-3 text-sm">
                    <p class="text-gray-500">
                        {{ $results->firstItem() }} sampai {{ $results->lastItem() }} dari {{ $results->total() }} hasil
                    </p>
                    {{ $results->links() }}
                </div>
            @endif
        </div>

        <div x-show="showDownloadModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            @click.self="showDownloadModal = false">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Download data minat</h2>
                        <p class="mt-1 text-sm text-gray-600">Pilih format file yang ingin diunduh.</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-500"
                        aria-label="Tutup modal" @click="showDownloadModal = false">&times;</button>
                </div>

                <label for="download-format" class="mt-5 block text-sm font-medium text-gray-700">Format</label>
                <select id="download-format" x-model="downloadFormat" class="select-primary mt-2 w-full text-sm">
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                </select>

                @php
                    $requestData = request()->only(['search', 'periode_id', 'paket_id', 'kelas_asal_id']);
                    $requestData['periode_id'] ??= $periodeList->first()?->id;
                @endphp

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="showDownloadModal = false">Batal</button>
                    <a x-show="downloadFormat === 'pdf'" target="_blank"
                        href="{{ route('admin.laporan.export.minat-siswa', ['format' => 'pdf', ...$requestData]) }}"
                        class="primary-button" @click="showDownloadModal = false">Download</a>
                    <a x-show="downloadFormat === 'csv'"
                        href="{{ route('admin.laporan.export.minat-siswa', ['format' => 'csv', ...$requestData]) }}"
                        class="primary-button" @click="showDownloadModal = false">Download</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>