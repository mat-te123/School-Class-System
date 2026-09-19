<x-app-layout title="Pendaftaran Pilihan">
    <div x-data="{
        showDeleteModal: false,
        showupdateModal: false,
        showaddmodal: false,
        confirmationData: '',
        addUrl: '',
        deleteUrl: '',
        updateUrl: '',
        Data: {},
    }" class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="text-4xl leading-9 font-semibold">
                Daftar Pilihan Siswa
            </h1>
            <span class="text-md leading-5 font-medium">
                Kelola daftar pilihan siswa yang telah mengisi formulir pendaftaran
            </span>
        </div>
        <div class="flex min-h-0 flex-1 flex-col gap-4 px-8 py-6">
            <div class="flex flex-row w-full justify-start gap-8">
                @php
                    $items = [
                        'Siswa' => [
                            'title' => 'Total Siswa',
                            'count' => $pendaftaran->count(),
                        ],
                        'is_filled' => [
                            'title' => 'Sudah Mengisi',
                            'count' => $pendaftaran->where('is_filled', true)->count(),
                        ],
                        'not_filled' => [
                            'title' => 'Belum Mengisi',
                            'count' => $pendaftaran->where('is_filled', false)->count(),
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
            <div class="flex flex-col w-full ">
                <h2 class="text-base font-semibold leading-6">
                    Daftar Pilihan Kelas Siswa
                </h2>
                <span class="text-xs font-medium leading-5">
                    Daftar Kelas yang dipilih oleh siswa pada formulir pendaftaran
                </span>
            </div>
            <form class="flex flex-row  p-3 justify-between bg-gray-200 border border-gray-300 rounded-lg w-full gap-2">
                <input type="text" placeholder="Cari nama, NISN, atau NIS..."
                    class="px-4 py-2 rounded-lg border border-gray-400 w-full">
                <select class="px-4 py-2 rounded-lg border border-gray-400 w-fit">
                    <option value="">Status : semua (sudah / belum )</option>
                    <option value="1">Status : sudah</option>
                    <option value="0">Status : belum</option>
                </select>

                <select class="px-4 py-2 rounded-lg border border-gray-400 w-fit">
                    <option value="">Pilihan Kelas : semua</option>
                    @foreach ($class as $key => $value)
                        <option value="{{ $value['title'] }}">Pilihan Kelas : {{ $value['title'] }}</option>
                    @endforeach
                </select>
            </form>
            <div class="min-h-0 flex-1 overflow-auto rounded-b-lg">
                <table class="h-full min-w-full border-separate border-spacing-0">
                    <thead>
                        <tr
                            class="bg-gray-300 text-xs font-semibold uppercase text-gray-600 [&_th]:px-4 [&_th]:py-3 [&_th]:text-left first:[&_th]:w-0 first:[&_th]:rounded-l-xl last:[&_th]:rounded-r-xl">
                            <th>#</th>
                            <th>Nama Lengkap</th>
                            <th>Status</th>
                            <th>Pilihan Kelas 1</th>
                            <th>Pilihan Kelas 2</th>
                            <th>Pilihan Kelas 3</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($pendaftaran->isEmpty())
                            <tr class="h-full">
                                <td colspan="6"
                                    class="h-full border border-gray-200 bg-white py-4 text-center align-middle">
                                    Tidak ada data pendaftaran siswa.
                                </td>
                            </tr>
                        @else
                            @foreach ($pendaftaran as $index => $item)
                                <tr class="bg-gray-50 hover:cursor-pointer hover:bg-gray-200 group">
                                    <th class="border-r border-t border-gray-200 p-2">
                                        {{ $index + 1 }}
                                    </th>
                                    <td>
                                        {{ $item->nama_lengkap }}
                                    </td>
                                    <td>
                                        @if ($item->is_filled)
                                            <span class="font-semibold text-green-600">Sudah Mengisi</span>
                                        @else
                                            <span class="font-semibold text-red-600">Belum Mengisi</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item->pilihan_kelas_1 }}
                                    </td>
                                    <td>
                                        {{ $item->pilihan_kelas_2 }}
                                    </td>
                                    <td>
                                        {{ $item->pilihan_kelas_3 }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

        </div>

        <x-flash-message />
    </div>
</x-app-layout>
