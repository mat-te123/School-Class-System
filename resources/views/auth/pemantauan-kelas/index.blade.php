<x-app-layout title="Pendaftaran Pilihan">
    <div class="flexflex-1 flex-col">
        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="text-4xl leading-9 font-semibold">
                Daftar Pilihan Siswa
            </h1>
            <span class="text-md leading-5 font-medium">
                Kelola daftar pilihan siswa yang telah mengisi formulir pendaftaran
            </span>
        </div>
        <div class="flex flex-1 flex-col gap-4 px-8 py-6">
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

            @php
                $detailPaketCollection = collect($summary['detail_paket']);
            @endphp


            <div class="flex flex-row w-full justify-start gap-8">

                @foreach ($paketMenu as $paket)
                    @php
                        $detail = $detailPaketCollection->firstWhere('id', $paket->id);
                        $total = $detail->total ?? 0;
                    @endphp


                    <div class="flex flex-col p-4 border border-gray-300 rounded-lg gap-2 w-fit">
                        <div class="flex flex-col border-l border-green-600 px-4">
                            <span class="text-lg font-semibold">{{ $paket->nama_menu }}</span>
                            <span class="text-base font-normal">{{ $total }} siswa</span>
                        </div>
                    </div>
                @endforeach

            </div>
            <div class="flex flex-col w-full ">
                <h2 class="text-base font-semibold leading-6">
                    Daftar Pilihan Kelas Siswa
                </h2>
                <span class="text-xs font-medium leading-5">
                    Daftar Kelas yang dipilih oleh siswa pada formulir pendaftaran
                </span>
            </div>

            <form method="GET" action="{{ route('admin.pemantauan-minat.index') }}"
                class="flex flex-row  p-3 justify-between bg-gray-200 border border-gray-300 rounded-lg w-full gap-2">
                <button type="submit" id="status-default" name="status" value="{{ $status }}" class="hidden"
                    tabindex="-1" aria-hidden="true"></button>

                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, NISN, atau NIS..."
                    class="px-4 rounded-lg border border-gray-400 w-full text-sm">

                @foreach (['sudah' => 'Sudah Mengisi', 'belum' => 'Belum Mengisi'] as $value => $label)
                    <button type="submit" name="status" value="{{ $value }}"
                        class="w-fit whitespace-nowrap text-sm rounded-lg px-4 {{ $status == $value ? 'bg-blue-600 text-white border border-blue-200' : 'bg-white text-gray-700  border border-gray-300 hover:border hover:border-[#3b82f6]' }}">
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
            <div class="flex flex-col rounded-xl overflow-hidden border border-gray-300 w-full">
                <table class="min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr
                            class="bg-gray-300 text-xs font-semibold uppercase text-gray-600 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left first:[&_th]:w-0">
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            @if ($status == 'sudah')
                                <th>Pilihan Kelas 1</th>
                                <th>Pilihan Kelas 2</th>
                                <th>Pilihan Kelas 3</th>
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
                                    <td colspan="6"
                                        class="h-full border border-gray-200 bg-white py-4 text-center align-middle">
                                        Belum ada siswa yang memilih.
                                    </td>
                                </tr>
                            @else
                                @foreach ($siswaSudahMemilih as $index => $item)
                                    <tr
                                        class="hover:cursor-pointer hover:bg-gray-200 group h-fit [&_td]:px-4 [&_td]:py-3">
                                        <th class="border-r border-t border-gray-200 p-2">
                                            {{ $index + 1 }}
                                        </th>
                                        <td class="px-3 lowercase">
                                            {{ $item->nama_lengkap }}
                                        </td>
                                        <td>
                                            {{ $item->pendaftaranPilihan->first()?->detailPendaftaran->firstWhere('urutan_pilihan', 1)?->paketMenuPilihan?->nama_menu ?? '—' }}
                                        </td>
                                        <td>
                                            {{ $item->pendaftaranPilihan->first()?->detailPendaftaran->firstWhere('urutan_pilihan', 2)?->paketMenuPilihan?->nama_menu ?? '—' }}
                                        </td>
                                        <td>
                                            {{ $item->pendaftaranPilihan->first()?->detailPendaftaran->firstWhere('urutan_pilihan', 3)?->paketMenuPilihan?->nama_menu ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    @elseif ($status == 'belum')
                        <tbody>
                            @if ($siswaBelumMemilih->isEmpty())
                                <tr>
                                    <td colspan="6"
                                        class="h-full border border-gray-200 bg-white py-4 text-center align-middle">
                                        Semua ssiswa telah memilih kelas.
                                    </td>
                                </tr>
                            @endif
                            @foreach ($siswaBelumMemilih as $index => $item)
                                <tr class="hover:cursor-pointer hover:bg-gray-200 group h-fit [&_td]:px-4 [&_td]:py-3">
                                    <th class="border-r border-t border-gray-200 p-2">
                                        {{ $index + 1 }}
                                    </th>
                                    <td class="px-3 lowercase">
                                        {{ $item->nama_lengkap }}
                                    </td>
                                    <td>
                                        {{ $item->nisn }}
                                    </td>
                                    <td>
                                        {{ $item->nisn }}
                                    </td>
                                    @if ($item->is_active == false)
                                        <td>
                                            <span class="negative-status">
                                                Tidak Aktif
                                            </span>
                                        </td>
                                    @else
                                        <td>
                                            <span class="positive-status">
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
                    <div class="flex flex-row py-3 px-4 bg-gray-300 w-full justify-between items-center">
                        <p class="text-black text-sm leading-5">
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
                                <a href="{{ $data->nextPageUrl() }}" class="text-gray-800 hover:underline">selanjutnya
                                    &gt;</a>
                            @else
                                <span class="text-gray-500 cursor-not-allowed">selanjutnya &gt;</span>
                            @endif
                        </div>
                    </div>
                @endif
            </div>



        </div>

        <x-flash-message />
    </div>
</x-app-layout>
