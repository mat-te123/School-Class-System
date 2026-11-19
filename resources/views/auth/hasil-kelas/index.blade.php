<x-app-layout title="Hasil Seleksi">
    <div x-data="{
        showConfirmModal: false,
        showDownloadModal: false,
        downloadFormat: 'pdf',
        ModalUrl: '',
        mainTitle: '',
        subTitle: '',
    }" @keydown.escape.window="showDownloadModal = false" class="flex flex-1 flex-col">

        <div class="flex flex-row justify-between items-end py-6 px-8 border border-b-black">
            <div class="flex flex-col">
                <div class="flex flex-row items-center gap-2">
                    <h1 class="mainTitle">
                        Hasil Seleksi
                    </h1>
                    @if (!$isFinal)
                        <span class="text-sm leading-5 font-medium bg-amber-50 text-amber-600 px-2 py-1 rounded-lg">
                            draft / semetara
                        </span>
                    @else
                        <span class="text-sm leading-5 font-medium bg-purple-50 text-purple-600 px-4 py-1 rounded-lg ">
                            final
                        </span>
                    @endif
                    @if ($isFinal)
                        @if (!$isPublished)
                            <span class="text-sm leading-5 font-medium bg-blue-50 text-blue-600 px-2 py-1 rounded-lg ">
                                belum dipublish
                            </span>
                        @else
                            <span class="text-sm leading-5 font-medium bg-green-50 text-green-600 px-2 py-1 rounded-lg">
                                sudah dipublish
                            </span>
                        @endif

                    @endif


                </div>

                <span class="subTitle">
                    Kelola dan tinjau hasil seleksi siswa sebelum difinalisasi
                </span>
            </div>
            @if ($isFinal && $isPublished)
                <button class="negative-primary-button w-fit whitespace-nowrap h-fit"
                    @click="showDownloadModal = true;">
                    Download data hasil
                </button>
            @endif
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
                        <button type="button"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                            @click="showDownloadModal = false">Batal</button>
                        <a x-show="downloadFormat === 'pdf'"
                            href="{{ route('admin.laporan.export.hasil-penjurusan', ['periode_id' => request('periode_id'), 'format' => 'pdf']) }}"
                            class="primary-button" @click="showDownloadModal = false">Download</a>
                        <a x-show="downloadFormat === 'csv'"
                            href="{{ route('admin.laporan.export.hasil-penjurusan', ['periode_id' => request('periode_id'), 'format' => 'csv']) }}"
                            class="primary-button" @click="showDownloadModal = false">Download</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 px-8 py-6">
            <a href="{{ route('admin-periode-penjurusan.index') }}" class="back-button">
                Kembali
            </a>
            @if ($summary['detail_kelas']->isEmpty())
                <h2 class="text-base font-semibold leading-6">
                    Ringkasan Data Umum
                </h2>
                <div
                    class="bg-gray-50 flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit shadow-lg shadow-black/5">
                    <div class="flex flex-col border-l border-red-600 px-4">
                        <span class="text-lg font-semibold">Data tidak ditemukan</span>
                        <span class="text-base font-normal">Tidak ada siswa yang ditempatkan pada kelas ini
                        </span>
                    </div>
                </div>
            @else
                <h2 class="text-base font-semibold leading-6">
                    Ringkasan Data Umum
                </h2>
                <div class="flex flex-row w-full justify-start gap-8">
                    @php
                        $items = [
                            'Siswa' => [
                                'title' => 'Total Siswa',
                                'count' => $summary['total_siswa'],
                            ],
                            'Total kelas' => [
                                'title' => 'Total Kelas',
                                'count' => $summary['total_kelas'],
                            ],
                        ];
                    @endphp
                    @if ($items['Total kelas']['count'] < 2)
                        <div
                            class="bg-gray-50 flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit shadow-lg shadow-black/5">
                            <div class="flex flex-col border-l border-green-600 px-4">
                                <span class="text-lg font-semibold">{{ $items['Siswa']['title'] }}</span>
                                <span class="text-base font-normal">{{ $items['Siswa']['count'] }} siswa</span>
                            </div>
                        </div>
                    @else
                        @foreach ($items as $key => $value)
                            <div
                                class="bg-gray-50 flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit shadow-lg shadow-black/5">
                                <div class="flex flex-col border-l border-green-600 px-4">
                                    <span class="text-lg font-semibold">{{ $value['title'] }}</span>
                                    <span class="text-base font-normal">{{ $value['count'] }} @if ($value['title'] === 'Total Siswa')
                                            siswa
                                        @else
                                            kelas
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @endif

                </div>

                <h2 class="text-base font-semibold leading-6">
                    Ringkasan Data Perkelas
                </h2>
                <div class="flex flex-row w-full justify-start gap-8">
                    @foreach ($summary['detail_kelas'] as $key => $value)
                        <div
                            class="flex flex-col p-4 border {{ $value['total'] > 32 ? 'bg-red-100' : 'bg-white' }} border-gray-300 rounded-lg gap-2 w-fit shadow-lg shadow-black/5">
                            <div
                                class="flex flex-col border-l {{ $value['total'] > 32 ? 'border-red-600' : 'border-green-600' }} px-4">
                                <span class="text-lg font-semibold">{{ $value['nama_menu'] }}</span>
                                <span class="text-base font-normal">{{ $value['total'] }} siswa</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex flex-row w-full justify-between items-end">
                <div class="flex flex-col w-full ">
                    <h2 class="mainTitle text-xl">
                        Daftar Hasil Seleksi Siswa
                    </h2>
                    <span class="subTitle">
                        Hasil seleksi siswa
                    </span>

                </div>
                @if (!$isFinal)
                    <button type="button"
                        @click="
                        showConfirmModal = true;
                        ModalUrl='{{ route('admin.hasil-penjurusan.lock') }}';
                        mainTitle='Finalisasi Hasil Seleksi Siswa' ;
                        subTitle='Apakah anda yakin ingin memfinalisasi hasil seleksi siswa untuk periode ini? Setelah difinalisasi, data tidak dapat diubah lagi.' "
                        class="primary-button w-fit whitespace-nowrap">
                        Finalisasi Hasil Seleksi
                    </button>
                @else
                    <div class="flex flex-row gap-2">
                        @if (!$isPublished)
                            <button type="button"
                                @click="
                            showConfirmModal = true; 
                            ModalUrl='{{ route('admin.hasil-penjurusan.unlock') }}'
                        ; mainTitle='Buka Finalisasi Hasil Seleksi Siswa';
                        subTitle='Apakah anda yakin ingin membuka finalisasi hasil seleksi siswa untuk periode ini? Setelah dibuka, data dapat diubah kembali.'"
                                class="negative-primary-button whitespace-nowrap w-fit">
                                Buka Finalisasi
                            </button>
                        @endif
                        @if ($isPublished)
                            <button type="button"
                                @click="showConfirmModal = true; ModalUrl='{{ route('admin.hasil-penjurusan.unpublish') }}';
                        mainTitle='Batalkan Publikasi Hasil Seleksi';
                        subTitle='Apakah anda yakin ingin membatalkan publikasi hasil seleksi siswa untuk periode ini? Setelah dibatalkan, data tidak dapat diakses oleh siswa.'"
                                class="primary-button whitespace-nowrap w-fit">
                                Batalkan Publikasi Hasil Seleksi
                            </button>
                        @else
                            <button type="button"
                                @click="showConfirmModal = true; ModalUrl='{{ route('admin.hasil-penjurusan.publish') }}';
                        mainTitle='Publikasikan Hasil Seleksi Siswa';
                        subTitle='Apakah anda yakin ingin mempublikasikan hasil seleksi siswa untuk periode ini? Setelah dipublikasikan, data dapat diakses oleh siswa.'"
                                class="primary-button whitespace-nowrap w-fit">
                                Publikasikan Hasil Seleksi
                            </button>
                        @endif

                    </div>
                @endif
            </div>

            <form method="GET" action="{{ route('admin.hasil-penjurusan.index') }}"
                class="flex flex-row  p-3 justify-between bg-gray-200 border border-gray-300 rounded-lg w-full gap-2">
                <input type="hidden" name="periode_id" value="{{ request()->input('periode_id') }}">
                <input type="text" placeholder="Cari nama, NISN, atau NIS..." name="search"
                    value="{{ request()->input('search') }}" oninput="this.value = this.value.toLowerCase()"
                    class="text-sm px-4 py-2 rounded-lg border border-gray-400 w-full">

                <select name="paket_menu_pilihan_id" onchange="this.form.submit()" class="select-primary text-sm">
                    <option value="">Pilihan Kelas : semua</option>
                    @foreach ($AllKelas as $value)
                        <option value="{{ $value->id }}"
                            {{ request()->input('paket_menu_pilihan_id') === $value->id ? 'selected' : '' }}>
                            Pilihan
                            Kelas :
                            {{ $value->nama_menu }}</option>
                    @endforeach
                </select>
            </form>
            <div class="flex flex-col rounded-xl overflow-hidden border border-gray-300 w-full">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr
                            class=" bg-gray-300 text-xs font-semibold uppercase text-gray-600 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left first:[&_th]:w-0 last:[&_th]:rounded-tr-xl">
                            <th class="rounded-tl-xl">#</th>
                            <th>Nama Siswa</th>
                            <th>NISN</th>
                            <th>NIS</th>
                            <th>Kelas Pilihan</th>
                            <th>Skor</th>
                            @if (!$isFinal)
                                <th>Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($results->isEmpty())
                            <tr class="h-full">
                                <td colspan="7"
                                    class="h-full border border-gray-200 bg-white py-4 text-center align-middle last:rounded-bl-xl last:rounded-br-xl">
                                    Data tidak ditemukan
                                </td>
                            </tr>
                        @else
                            @foreach ($results as $index => $item)
                                <tr class="bg-gray-50 hover:bg-gray-200 group h-fit [&_td]:px-4 [&_td]:py-3">
                                    <th class="border-r border-t border-gray-200 p-2">
                                        {{ $index + 1 }}
                                    </th>
                                    <td>
                                        <div class="flex flex-row items-center gap-2 lowercase justify-between">

                                            {{ $item->siswa->nama_lengkap }}
                                            <a href="{{ route('siswa.show', $item->siswa->id) }}"
                                                class="opacity-0 group-hover:opacity-100 bg-white hover:bg-gray-300 rounded-lg p-1 transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                                <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow icon"
                                                    class="w-4 h-4">
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item->siswa->nisn }}
                                    </td>
                                    <td>
                                        {{ $item->siswa->nis }}
                                    </td>
                                    <td>
                                        {{ $item->paketMenuPilihan->nama_menu }}
                                    </td>
                                    <td>
                                        {{ $item->skor_penempatan }}
                                    </td>
                                    @if (!$isFinal)
                                        <td>
                                            <button type="button"
                                                @click="window.location.href='{{ route('admin.hasil-penjurusan.show-siswa', ['siswaId' => $item->siswa->id]) }}'"
                                                class="p-1 bg-blue-50 hover:bg-blue-300 rounded-lg transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                                <img src="{{ asset('Icon/Edit.svg') }}" alt="edit icon">
                                            </button>
                                        </td>
                                    @endif

                                </tr>
                            @endforeach


                        @endif
                    </tbody>
                </table>
                @if ($results->hasPages())
                    <div class="flex flex-row py-3 px-8 bg-gray-300 w-full justify-between items-center">
                        <p class="text-black text-sm leading-5">
                            {{ $results->firstItem() ?? 0 }} sampai {{ $results->lastItem() ?? 0 }} dari
                            {{ $results->total() }} hasil
                        </p>
                        <div class="flex flex-row w-fit gap-3 items-center">
                            @if ($results->onFirstPage())
                                <span class="text-gray-400 cursor-not-allowed">&lt; sebelum</span>
                            @else
                                <a href="{{ $results->previousPageUrl() }}"
                                    class="text-gray-800  hover:underline">&lt;
                                    sebelum</a>
                            @endif
                            <div class="flex flex-row gap-2 ">
                                @for ($halaman = 1; $halaman <= $results->lastPage(); $halaman++)
                                    <a href="{{ $results->url($halaman) }}"
                                        class="rounded {{ $halaman == $results->currentPage() ? ' text-gray-800 font-bold' : 'text-gray-500 hover:text-gray-800 font-bold' }}">
                                        {{ $halaman }}
                                    </a>
                                @endfor
                            </div>
                            @if ($results->hasMorePages())
                                <a href="{{ $results->nextPageUrl() }}"
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
        <x-hasil-seleksi-confirm-modal />

    </div>


</x-app-layout>
