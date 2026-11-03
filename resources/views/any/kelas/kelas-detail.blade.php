<x-app-layout title="Kelas Detail" type='siswa'>

    <div class="flex flex-col py-6 px-8 border border-b-black">
        <h1 class="text-3xl leading-9 font-semibold">
            {{ $paketMenu->nama_menu }}
        </h1>
        <span class="text-sm leading-5 font-medium">
            Lihat detail kelas {{ $paketMenu->nama_menu }} dan mata pelajaran yang tersedia
        </span>
    </div>
    <div class="flex flex-col pt-6 px-8 gap-4">
        <a href="{{ url()->previous() }}" class="back-button">
            Kembali
        </a>
        {{-- Data Card --}}
        <div class="flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $paketMenu->nama_menu }}
                    </h1>
                    <span class="text-sm text-gray-500">
                        Rumpun {{ $paketMenu->rumpun }}
                    </span>
                </div>
            </div>
            <div class="flex flex-row justify-between w-full">
                @php
                    $data = [
                        'Kuota Kapasitas' => $paketMenu->kuota_kapasitas ? $paketMenu->kuota_kapasitas : '-',
                        'Kuota Terisi' => $paketMenu->kuota_terisi ? $paketMenu->kuota_terisi : '-',
                        'Status' => $paketMenu->is_active ? 'Aktif' : 'Tidak Aktif',
                    ];
                @endphp
                @foreach ($data as $key => $value)
                    <div class="flex flex-col gap-1 w-full">
                        <span class="text-sm text-gray-500">
                            {{ $key }}
                        </span>
                        <span class="text-base font-semibold">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach

            </div>

        </div>
    </div>
    <div class="flex flex-col pt-6 px-8 gap-6">
        <div class="flex flex-col">
            <h2 class="text-xl font-semibold">
                Daftar Mata Pelajaran
            </h2>
            <span class="text-sm text-gray-500">
                Bobot penilaian dari masing-masing mata pelajaran untuk kelas {{ $paketMenu->nama_menu }} dalam persen
            </span>
        </div>
        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr
                    class="bg-[#D1D5DB] text-slate-700 font-semibold border-b border-slate-200 [&_th]:p-3 first:[&_th]:rounded-l-xl first:[&_th]:w-0 last:[&_th]:rounded-r-xl">
                    <th>No</th>
                    <th>Mata Pelajaran</th>
                    <th>Bobot ( % )</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($kriteriaBobotMenu as $index => $item)
                    <tr class="hover:bg-slate-50 [&_td]:p-3 [&_td]:border-b border-slate-200 cursor-pointer">
                        <td class="p-3 font-medium text-slate-800">{{ $index + 1 }}</td>
                        <td class="p-3 font-medium text-slate-800">
                            {{ $item->masterMataPelajaran?->nama_mapel ?? 'Mapel Tidak Ditemukan' }}</td>
                        <td class="p-3 font-medium text-slate-800">{{ $item['bobot_persen'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
