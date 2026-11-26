<x-app-layout title="Daftar Pilihan Siswa">
    <div class="flex flex-1 flex-col" x-data="{
        showConfirmModal: false,
        showDownloadModal: false,
        downloadFormat: 'pdf',
    }" @keydown.escape.window="showDownloadModal = false">

        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="mainTitle">
                Daftar Pilihan Siswa
            </h1>
            <span class="subTitle">
                Kelola daftar pilihan siswa yang telah mengisi formulir pendaftaran
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-4 px-8 py-6">
            <a href={{ route('periode-penjurusan.show', ['id' => request('periode_id')]) }} class="primary-button w-fit">
                kembali


            </a>
            <div class="flex flex-row w-full justify-start gap-8">
                @php
                    $items = [
                        'Siswa' => [
                            'title' => 'Total Siswa',
                            'count' => $summary['total_siswa'],
                        ],
                        'is_filled' => [
                            'title' => 'Sudah Mengisi',
                            'count' => $summary['sudah_memilih'],
                        ],
                        'not_filled' => [
                            'title' => 'Belum Mengisi',
                            'count' => $summary['belum_memilih'],
                        ],
                    ];
                @endphp
                @foreach ($items as $key => $value)
                    <div class="bg-gray-50  flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit">
                        <div class="flex flex-col border-l border-green-600 px-4">
                            <span class="text-lg font-semibold">{{ $value['title'] }}</span>
                            <span class="text-base font-normal">{{ $value['count'] }} siswa</span>
                        </div>
                    </div>
                @endforeach
            </div>


            @php
                $detailPaketCollection = collect($summary['detail_paket']);
            @endphp

            @if (!$paketMenu->isEmpty())
                <h2 class="text-base font-semibold leading-6">
                    Ringkasan Data Perkelas
                </h2>
                <div class="flex flex-row w-full justify-start gap-8">

                    @foreach ($paketMenu as $paket)
                        @php
                            $detail = $detailPaketCollection->firstWhere('id', $paket->id);
                            $total = $detail->total ?? 0;
                        @endphp


                        <div
                            class="shadow-2xl shadow-black/10 flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit">
                            <div class="flex flex-col border-l border-green-600 px-4">
                                <span class="text-lg font-semibold">{{ $paket->nama_menu }}</span>
                                <span class="text-base font-normal">{{ $total }} siswa</span>
                            </div>
                        </div>
                    @endforeach

                </div>
            @endif

            <div class="flex flex-row w-full justify-between items-end">
                <div class="flex flex-col w-full ">
                    <h2 class="text-xl font-semibold">
                        Daftar Pilihan Kelas Siswa
                    </h2>
                    <span class="text-sm text-gray-500">
                        Daftar Kelas yang dipilih oleh siswa pada formulir pendaftaran
                    </span>
                </div>
                <div class="flex gap-2">
                    @if (!$siswaSudahMemilih->isEmpty())
                        <button type="button" @click="showDownloadModal = true"
                            class="negative-primary-button w-fit whitespace-nowrap">
                            Download data
                        </button>
                    @endif

                </div>
            </div>

            <div x-show="showDownloadModal" x-cloak x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4"
                @click.self="showDownloadModal = false">
                <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl" @click.stop>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">Download data minat siswa</h2>
                            <p class="mt-1 text-sm text-gray-600">Pilih format file yang ingin diunduh.</p>
                        </div>
                        <button type="button" class="text-2xl leading-none text-gray-500" aria-label="Tutup modal"
                            @click="showDownloadModal = false">&times;</button>
                    </div>

                    <label for="minat-download-format"
                        class="mt-5 block text-sm font-medium text-gray-700">Format</label>
                    <select id="minat-download-format" x-model="downloadFormat"
                        class="select-primary mt-2 w-full text-sm">
                        <option value="pdf">PDF</option>
                        <option value="csv">CSV</option>
                    </select>

                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="showDownloadModal = false">Batal</button>
                        <a x-show="downloadFormat === 'pdf'"
                            href="{{ route('admin.laporan.export.minat-siswa', ['periode_id' => request('periode_id'), 'paket_id' => request('kelas_pilihan'), 'format' => 'pdf']) }}"
                            class="primary-button" @click="showDownloadModal = false">Download</a>
                        <a x-show="downloadFormat === 'csv'"
                            href="{{ route('admin.laporan.export.minat-siswa', ['periode_id' => request('periode_id'), 'paket_id' => request('kelas_pilihan'), 'format' => 'csv']) }}"
                            class="primary-button" @click="showDownloadModal = false">Download</a>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.pemantauan-minat.index') }}"
                class="flex flex-row  p-3 justify-between bg-gray-200 border border-gray-300 rounded-lg w-full gap-2">
                <input type="hidden" name="periode_id" value="{{ request()->input('periode_id') }}">

                <button type="submit" id="status-default" name="status" value="{{ $status }}" class="hidden"
                    tabindex="-1" aria-hidden="true"></button>

                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, NISN, atau NIS..." oninput="this.value = this.value.toLowerCase()"
                    class="px-4 rounded-lg border border-gray-400 w-full text-sm">

                @foreach (['sudah' => 'Sudah Mengisi', 'belum' => 'Belum Mengisi'] as $value => $label)
                    <button type="submit" name="status" value="{{ $value }}"
                        class="w-fit whitespace-nowrap text-sm rounded-lg px-4 py-2 {{ $status == $value ? 'bg-blue-600 text-white border border-blue-200' : 'bg-white text-gray-700  border border-gray-300 hover:border hover:border-[#3b82f6]' }}">
                        {{ $label }}
                    </button>
                @endforeach


                @if ($status == 'sudah')
                    <select name="kelas_pilihan" onchange="this.form.submit()" class="select-primary text-sm">
                        <option value="">Pilihan Kelas : semua</option>
                        @foreach ($paketMenu as $value)
                            <option value="{{ $value->id }}" @selected(request('kelas_pilihan') == $value->id)>
                                Pilihan Kelas : {{ $value->nama_menu }}
                            </option>
                        @endforeach
                    </select>
                @endif

            </form>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            @if ($status == 'sudah')
                                @for ($i = 1; $i <= $currentPeriodeSelectionValue; $i++)
                                    <th> Pilihan {{ $i }} </th>
                                @endfor
                            @else
                                <th>NISN</th>
                                <th>NIS</th>
                                <th>Status</th>
                            @endif
                        </tr>
                    </thead>
                    @if ($status == 'sudah')
                        <tbody>
                            @if ($siswaSudahMemilih->isEmpty())
                                <tr class="h-full">
                                    <td colspan="6" class="text-center py-6">
                                        <div class="flex flex-col items-center justify-center py-12 px-2  ">
                                            <h3 class="mainTitle text-lg">
                                                Data tidak ditemukan

                                            </h3>

                                            <p class="subTitle text-sm">
                                                Belum ada siswa yang memilih kelas
                                            </p>

                                        </div>
                                    </td>
                                </tr>
                            @else
                                @foreach ($siswaSudahMemilih as $index => $item)
                                    <tr class="group">
                                        <th>
                                            {{ $index + 1 }}
                                        </th>
                                        <td>
                                            <div class="flex flex-row items-center gap-2 lowercase justify-between">
                                                {{ $item->nama_lengkap }}
                                                <a href="{{ route('siswa.show', $item->id) }}"
                                                    class="opacity-0 group-hover:opacity-100 bg-white hover:bg-gray-300 rounded-lg p-1 transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                                    <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow icon"
                                                        class="w-4 h-4">
                                                </a>
                                            </div>
                                        </td>
                                        @for ($i = 1; $i <= $currentPeriodeSelectionValue; $i++)
                                            <td>
                                                {{ $item->pendaftaranPilihan->first()?->detailPendaftaran->firstWhere('urutan_pilihan', $i)?->paketMenuPilihan?->nama_menu ?? '—' }}
                                            </td>
                                        @endfor
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    @elseif ($status == 'belum')
                        <tbody>
                            @if ($siswaBelumMemilih->isEmpty())
                                <tr>
                                    <td colspan="6" class="text-center py-6">
                                        <div class="flex flex-col items-center justify-center py-12 px-2  ">
                                            <h3 class="mainTitle text-lg">
                                                Data tidak ditemukan

                                            </h3>

                                            <p class="subTitle text-sm">
                                                Semua siswa sudah memilih kelas
                                            </p>

                                        </div>
                                    </td>
                                </tr>
                            @endif
                            @foreach ($siswaBelumMemilih as $index => $item)
                                <tr class="group">
                                    <th>
                                        {{ $index + 1 }}
                                    </th>
                                    <td>
                                        <div class="flex flex-row items-center gap-2 lowercase justify-between">
                                            {{ $item->nama_lengkap }}
                                            <a href="{{ route('siswa.show', $item->id) }}"
                                                class="opacity-0 group-hover:opacity-100 bg-white hover:bg-gray-300 rounded-lg p-1 transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                                <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow icon"
                                                    class="w-4 h-4">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->nisn }}
                                    </td>
                                    <td>
                                        {{ $item->nisn }}
                                    </td>
                                    @if ($item->is_active == false)
                                        <td>
                                            <span class="status-badge status-badge-danger">
                                                Tidak Aktif
                                            </span>
                                        </td>
                                    @else
                                        <td>
                                            <span class="status-badge status-badge-success">
                                                Aktif
                                            </span>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    @endif
                </table>
                @php $data = $status == 'sudah' ? $siswaSudahMemilih : $siswaBelumMemilih; @endphp

                @if ($data->hasPages())
                    <div class="flex flex-row py-3 px-4 bg-[#f3f4f6] w-full justify-between items-center text-sm">
                        <p class="text-gray-500 text-sm leading-5">
                            {{ $data->firstItem() }} sampai {{ $data->lastItem() }} dari {{ $data->total() }} hasil
                        </p>
                        <div class="flex flex-row w-fit gap-3 items-center">
                            @if ($data->onFirstPage())
                                <span class="text-gray-400 cursor-not-allowed">&lt; sebelum</span>
                            @else
                                <a href="{{ $data->previousPageUrl() }}" class="text-gray-800 hover:underline">&lt;
                                    sebelum</a>
                            @endif

                            <div class="flex flex-row gap-2">
                                @foreach (range(1, $data->lastPage()) as $halaman)
                                    <a href="{{ $data->url($halaman) }}"
                                        class="font-bold {{ $halaman == $data->currentPage() ? 'text-gray-800' : 'text-gray-500 hover:text-gray-800' }}">
                                        {{ $halaman }}
                                    </a>
                                @endforeach
                            </div>

                            @if ($data->hasMorePages())
                                <a href="{{ $data->nextPageUrl() }}"
                                    class="text-gray-800 hover:underline">selanjutnya
                                    &gt;</a>
                            @else
                                <span class="text-gray-500 cursor-not-allowed">selanjutnya &gt;</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>



        </div>
        {{-- <div x-show="showConfirmModal"
            class="flex flex-col fixed top-0 left-0 w-full h-full bg-black/50 z-50 justify-center items-center"
            style="display: none;">
            <div class="flex flex-col bg-white rounded-lg p-6 gap-4 w-[36rem]" @click.away="showConfirmModal = false">
                <div class="flex flex-col gap-1">
                    <h2 class="mainTitle">
                        Konfirmasi Proses Data
                    </h2>
                    <p class="subTitle">
                        Data pilihan siswa akan diproses menjadi hasil penempatan. Tindakan ini tidak dapat dibatalkan.
                    </p>
                </div>

                <div class="flex flex-row justify-end gap-2">
                    <button type="button" @click="showConfirmModal = false"
                        class="text-sm px-4 py-2 text-white bg-red-500 rounded-md hover:bg-red-600">
                        Batal
                    </button>
                    <form method="POST" action={{ route('admin.hasil-penjurusan.process') }}>
                        @csrf
                        <input type="hidden" name="periode_id" value="{{ request('periode_id') }}">
                        <button type="submit"
                            class="text-sm px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Proses Data
                        </button>
                    </form>
                </div>
            </div>


        </div> --}}
    </div>

</x-app-layout>
