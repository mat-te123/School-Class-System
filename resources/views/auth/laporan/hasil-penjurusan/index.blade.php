<x-app-layout>
    <div x-data="{
        showDownloadModal: false,
        downloadFormat: 'pdf',
    }" @keydown.escape.window="showDownloadModal = false">


        <div class="flex flex-col border-b border-black px-4 py-6 sm:px-8">
            <h1 class="mainTitle">
                Hasil Penjurusan
            </h1>
            <span class="subTitle">Laporan hasil penjurusan siswa</span>
        </div>
        <div class="flex flex-col gap-6 px-4 py-6 sm:px-8">
            <section class="flex flex-row items-end justify-between">
                <div class="flex flex-col ">
                    <h2 class="mainTitle text-lg">
                        Data Hasil Penjurusan
                    </h2>
                    <p class="subTitle text-sm">
                        Pantau hasil penjurusan siswa yang sudah dilakukan. Gunakan filter untuk menampilkan data sesuai
                        kebutuhan.
                    </p>
                </div>
                @php
                    $requestData = request()->only(['search', 'periode_id', 'paket_id', 'kelas_asal_id', 'mekanisme']);
                @endphp
                <button class="negative-primary-button w-fit whitespace-nowrap h-fit" @disabled($periodeList->isEmpty())
                    @click="showDownloadModal = true;">
                    Download data hasil
                </button>

            </section>


            <form method="GET" action="{{ route('admin.laporan.hasil-penjurusan') }}"
                class="flex flex-row bg-gray-100 p-3 w-full border border-gray-400 rounded-lg gap-3 items-center">


                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, atau NISN"
                    oninput="this.value = this.value.toLowerCase()"
                    class="w-full py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg text-sm">



                <select class="select-primary text-sm" name="periode_id" onchange="this.form.submit()">
                    @foreach ($periodeList as $periode)
                        <option value="{{ $periode->id }}"
                            {{ request('periode_id') == $periode->id ? 'selected' : '' }}>
                            {{ $periode->nama_periode }}
                        </option>
                    @endforeach
                </select>
                <select class="select-primary text-sm" name="paket_id" onchange="this.form.submit()">
                    <option value="">semua Paket</option>
                    @foreach ($periodePaketList as $paket)
                        <option value="{{ $paket->id }}" {{ request('paket_id') == $paket->id ? 'selected' : '' }}>
                            {{ $paket->nama_menu }}
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
                            <th>Periode</th>
                            <th>Kelas Asal</th>
                            <th>Paket Penempatan</th>
                            <th>Skor</th>
                            <th>Rata-rata 6 Mapel</th>
                            <th>Mekanisme</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($results as $row)
                            <tr>
                                <td>{{ $row->siswa?->nisn ?? '-' }}</td>
                                <td>{{ $row->siswa?->nama_lengkap ?? '-' }}</td>
                                <td>{{ $periodeList->firstWhere('id', $row->periode_pendaftaran_id)?->nama_periode ?? '-' }}</td>
                                <td>{{ $row->siswa?->kelasAsalRelation?->nama_kelas ?? '-' }}</td>
                                <td>{{ $row->paketMenuPilihan?->nama_menu ?? '-' }}</td>
                                <td>{{ $row->skor_penempatan ?? '-' }}</td>
                                <td>{{ $row->rata_6_mapel ?? '-' }}</td>
                                <td>{{ $row->mekanisme ?? '-' }}</td>
                            </tr>
                        @empty
                            @if ($periodeList->isEmpty())
                                <tr>
                                    <td colspan="8">Belum ada periode penjurusan.</td>
                                </tr>
                            @elseif (request()->hasAny(['search', 'periode_id', 'paket_id']))
                                <tr>
                                    <td colspan="8">Tidak ada hasil untuk filter ini.</td>
                                </tr>
                            @else
                                <tr>
                                    <td colspan="8">Tidak ada data hasil penjurusan.</td>
                                </tr>
                            @endif
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div x-show="showDownloadModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
            @click.self="showDownloadModal = false">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold">Download data hasil</h2>
                        <p class="mt-1 text-sm text-gray-600">Pilih format file yang ingin diunduh.</p>
                    </div>
                    <button type="button" class="text-2xl leading-none text-gray-500" aria-label="Tutup modal"
                        @click="showDownloadModal = false">&times;</button>
                </div>

                <label for="download-format" class="mt-5 block text-sm font-medium text-gray-700">Format</label>
                <select id="download-format" x-model="downloadFormat" class="select-primary mt-2 w-full text-sm">
                    <option value="pdf">PDF</option>
                    <option value="csv">CSV</option>
                </select>

                <div class="mt-6 flex justify-end gap-2">
                    @php
                        $requestData = request()->only(['periode_id', 'paket_id', 'kelas_asal_id', 'mekanisme']);
                    @endphp
                    <button type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="showDownloadModal = false">Batal</button>
                    <a x-show="downloadFormat === 'pdf'" target="_blank"
                        href="{{ route('admin.laporan.export.hasil-penjurusan', ['format' => 'pdf', ...$requestData]) }}"
                        class="primary-button" @click="showDownloadModal = false">Download</a>
                    <a x-show="downloadFormat === 'csv'"
                        href="{{ route('admin.laporan.export.hasil-penjurusan', ['format' => 'csv', ...$requestData]) }}"
                        class="primary-button" @click="showDownloadModal = false">Download</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
