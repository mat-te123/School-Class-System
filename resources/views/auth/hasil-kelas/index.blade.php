<x-app-layout title="Hasil Seleksi">
    <div x-data="{
        showConfirmModal: false,
        ModalUrl: '',
        mainTitle: '',
        subTitle: '',
    }" class="flex min-h-0 flex-1 flex-col">

        <div class="flex flex-col py-6 px-8 border border-b-black">
            <div class="flex flex-row items-center gap-2">
                <h1 class="text-4xl leading-9 font-semibold">
                    Hasil Seleksi
                </h1>
                @if (!$isFinal)
                    <span
                        class="text-sm leading-5 font-medium bg-amber-50 text-amber-600 px-2 py-1 rounded-lg border border-amber-600">
                        draft / semetara
                    </span>
                @else
                    <span
                        class="text-sm leading-5 font-medium bg-green-50 text-green-600 px-4 py-1 rounded-lg border border-green-600">
                        final
                    </span>
                @endif

            </div>

            <span class="text-md leading-5 font-medium">
                Kelola dan tinjau hasil seleksi siswa sebelum difinalisasi
            </span>
        </div>

        <div class="flex min-h-0 flex-1 flex-col gap-4 px-8 py-6">
            <a href="{{ url()->previous() }}" class="back-button">
                Kembali
            </a>
            <h2 class="text-base font-semibold leading-6">
                Ringkasan Data Umum
            </h2>
            <div class="flex flex-row w-full justify-start gap-8">
                @php
                    $items = [
                        'Siswa' => [
                            'title' => 'Total Siswa',
                            'count' => $results->count(),
                        ],
                        'is_filled' => [
                            'title' => 'Sudah Mengisi',
                            'count' => $results->where('is_filled', true)->count(),
                        ],
                        'not_filled' => [
                            'title' => 'Belum Mengisi',
                            'count' => $results->where('is_filled', false)->count(),
                        ],
                    ];
                @endphp
                @foreach ($items as $key => $value)
                    <div class="flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit">
                        <div class="flex flex-col border-l border-green-600 px-4">
                            <span class="text-lg font-semibold">{{ $value['title'] }}</span>
                            <span class="text-base font-normal">{{ $value['count'] }} siswa</span>
                        </div>
                    </div>
                @endforeach
            </div>
            <h2 class="text-base font-semibold leading-6">
                Ringkasan Data Perkelas
            </h2>
            <div class="flex flex-row w-full justify-start gap-8">
                @php
                    $class = [
                        'Kelas A' => [
                            'title' => 'Kelas A',
                            // ini place holder
                            'count' => 32,
                            'rumpun' => 'eksakta',
                        ],
                        'Kelas B' => [
                            'title' => 'Kelas B',
                            // ini place holder
                            'count' => 28,
                            'rumpun' => 'eksakta',
                        ],
                        'Kelas C' => [
                            'title' => 'Kelas C',
                            // ini place holder
                            'count' => 30,
                            'rumpun' => 'sosial',
                        ],
                        'Kelas D' => [
                            'title' => 'Kelas D',
                            // ini place holder
                            'count' => 25,
                            'rumpun' => 'sosial',
                        ],
                    ];
                @endphp
                @foreach ($class as $key => $value)
                    <div class="flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit">
                        <div class="flex flex-col border-l border-green-600 px-4">
                            <span class="text-lg font-semibold">{{ $value['title'] }} - {{ $value['rumpun'] }}</span>
                            <span class="text-base font-normal">{{ $value['count'] }} siswa • Pilihan 1</span>
                        </div>
                    </div>
                @endforeach

            </div>
            <div class="flex flex-row w-full justify-between items-end">
                <div class="flex flex-col w-full ">
                    <h2 class="text-xl font-semibold leading-6">
                        Daftar Pilihan Kelas Siswa
                    </h2>
                    <span class="text-sm leading-5 text-gray-500">
                        Daftar Kelas yang dipilih oleh siswa pada formulir results
                    </span>

                </div>
                @if (!$isFinal)
                    <button type="button"
                        @click="
                        showConfirmModal = true;
                        ModalUrl='{{ route('admin.hasil-penjurusan.lock') }}';
                        mainTitle='Finalisasi Hasil Seleksi Siswa' ;
                        subTitle='Apakah anda yakin ingin memfinalisasi hasil seleksi siswa untuk periode ini? Setelah difinalisasi, data tidak dapat diubah lagi.' "
                        class="bg-black text-sm text-white whitespace-nowrap h-fit px-4 py-2 rounded-lg hover:bg-gray-800 transition-all duration-100 ease-in-out active:shadow-lg">
                        Finalisasi Hasil Seleksi
                    </button>
                @else
                    <div class="flex flex-row gap-2">
                        <button type="button"
                            @click="
                            showConfirmModal = true; 
                            ModalUrl='{{ route('admin.hasil-penjurusan.unlock') }}'
                        ; mainTitle='Buka Finalisasi Hasil Seleksi Siswa';
                        subTitle='Apakah anda yakin ingin membuka finalisasi hasil seleksi siswa untuk periode ini? Setelah dibuka, data dapat diubah kembali.'"
                            class="bg-black
                            text-sm text-white whitespace-nowrap h-fit px-4 py-2 rounded-lg hover:bg-gray-800
                            transition-all duration-100 ease-in-out active:shadow-lg">
                            Buka Finalisasi
                        </button>
                        <button type="button"
                            @click="showConfirmModal = true; ModalUrl='{{ route('admin.hasil-penjurusan.publish') }}';
                        mainTitle='Publikasikan Hasil Seleksi Siswa';
                        subTitle='Apakah anda yakin ingin mempublikasikan hasil seleksi siswa untuk periode ini? Setelah dipublikasikan, data dapat diakses oleh siswa.'"
                            class="bg-black text-sm text-white whitespace-nowrap h-fit px-4 py-2 rounded-lg hover:bg-gray-800 transition-all duration-100 ease-in-out active:shadow-lg">
                            Publikasikan Hasil Seleksi
                        </button>
                    </div>
                @endif

            </div>

            <form method="GET" action="{{ route('admin.hasil-penjurusan.index') }}"
                class="flex flex-row  p-3 justify-between bg-gray-200 border border-gray-300 rounded-lg w-full gap-2">
                <input type="text" placeholder="Cari nama, NISN, atau NIS..."
                    class="text-sm px-4 py-2 rounded-lg border border-gray-400 w-full">

                <select name="paket_menu_pilihan_id" onchange="this.form.submit()" class="select-primary text-sm">
                    <option value="">Pilihan Kelas : semua</option>
                    @foreach ($summary['detail_kelas'] as $value)
                        <option value="{{ $value['id'] }}">Pilihan Kelas :
                            {{ $value['nama_menu'] }}</option>
                    @endforeach
                </select>
            </form>
            <div class="flex flex-col rounded-xl overflow-hidden border border-gray-300 w-full">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr
                            class="bg-gray-300 text-xs font-semibold uppercase text-gray-600 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left first:[&_th]:w-0">
                            <th>#</th>
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
                                <td colspan="6"
                                    class="h-full border border-gray-200 bg-white py-4 text-center align-middle">
                                    Tidak ada data results siswa.
                                </td>
                            </tr>
                        @else
                            @foreach ($results as $index => $item)
                                <tr class="hover:cursor-pointer hover:bg-gray-200 group h-fit [&_td]:px-4 [&_td]:py-3">
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
                                                @click="
                                                showupdateModal = true;
                                                studentData = {{ json_encode($item) }};
                                                editUrl = '{{ route('siswa.update', $item->id) }}';
                                        "
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
            </div>

        </div>
        <pre>
            {{ json_encode($isFinal, JSON_PRETTY_PRINT) }}
        </pre>
        <x-hasil-seleksi-confirm-modal />

    </div>

</x-app-layout>
