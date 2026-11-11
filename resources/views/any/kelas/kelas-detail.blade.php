<x-app-layout title="Kelas Detail" type="{{ $isAdmin ? 'admin' : 'siswa' }}">
    <div x-data="{
        showDeleteModal: false,
        showupdateModal: false,
        confirmationData: '',
        deleteUrl: '',
        updateUrl: '',
        item: { bobot_persen: '', master_mata_pelajaran: { nama_mapel: '' } },
    }">
        <div class ="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="mainTitle">
                {{ $paketMenu->nama_menu }}
            </h1>
            <span class="subTitle">
                Lihat detail kelas {{ $paketMenu->nama_menu }} dan mata pelajaran yang tersedia
            </span>
        </div>
        <div class="flex flex-col p-8 gap-4">
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
        <div class="flex flex-col pb-6 px-8 gap-6">
            <div class="flex flex-col">
                <h2 class="mainTitle text-xl">
                    Daftar Mata Pelajaran
                </h2>
                <span class="subTitle">
                    Bobot penilaian dari masing-masing mata pelajaran untuk kelas {{ $paketMenu->nama_menu }} dalam
                    persen
                </span>
            </div>
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr
                        class="bg-[#D1D5DB] text-slate-700 font-semibold border-b border-slate-200 [&_th]:p-3 first:[&_th]:rounded-tl-xl first:[&_th]:w-0 last:[&_th]:rounded-tr-xl">
                        <th>No</th>
                        <th>Mata Pelajaran</th>
                        <th>Bobot ( % )</th>
                        @if ($isAdmin)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($kriteriaBobotMenu as $index => $item)
                        <tr class="hover:bg-slate-50 [&_td]:p-3 [&_td]:border-b border-slate-200 cursor-pointer">
                            <td class="p-3 font-medium text-slate-800">{{ $index + 1 }}</td>
                            <td class="p-3 font-medium text-slate-800">
                                {{ $item->masterMataPelajaran?->nama_mapel ?? 'Mapel Tidak Ditemukan' }}</td>
                            <td class="p-3 font-medium text-slate-800">{{ $item['bobot_persen'] }}</td>
                            @if ($isAdmin)
                                <td class="p-3 font-medium text-slate-800">
                                    <div class="flex flex-row gap-2">
                                        <button
                                            @click="showupdateModal = true; item = {{ json_encode($item) }}; confirmationData = '{{ $item->masterMataPelajaran?->nama_mapel ?? 'Mapel Tidak Ditemukan' }}'; updateUrl = '{{ route('kriteria-bobot.update', ['id' => $item->id]) }}';"
                                            class="p-1 bg-blue-50 hover:bg-blue-300 rounded-lg transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                            <img src="{{ asset('Icon/Edit.svg') }}" alt="edit icon"></button>
                                        <button
                                            @click="showDeleteModal = true; confirmationData = '{{ $item->masterMataPelajaran?->nama_mapel ?? 'Mapel Tidak Ditemukan' }}'; deleteUrl = '{{ route('kriteria-bobot.destroy', ['id' => $item->id]) }}';"
                                            class="p-1 bg-red-50
                                            hover:bg-red-300 rounded-lg transition-all duration-100 ease-in-out
                                            active:scale-110 active:shadow-lg">
                                            <img src="{{ asset('Icon/Trash.svg') }}" alt="trash icon"></button>
                                        </form>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                @php
                    $totalBobot = $kriteriaBobotMenu->sum('bobot_persen');
                @endphp
                <tfoot
                    class=" bg-[#D1D5DB] text-slate-700 font-semibold first:[&_td]:rounded-bl-xl last:[&_td]:rounded-br-xl">
                    <tr>

                        <td colspan="2" class="p-3 text-left">Total Bobot :</td>

                        <td class="p-3 text-slate-900">{{ $totalBobot }}%</td>


                        @if ($isAdmin)
                            @if ($totalBobot > 100)
                                <td class="p-3 text-red-600 font-semibold">Bobot melebihi 100%</td>
                            @elseif ($totalBobot < 100)
                                <td class="p-3 text-yellow-600 font-semibold">Bobot kurang dari 100%</td>
                            @endif
                        @endif

                    </tr>
                </tfoot>
            </table>


        </div>
        <x-delete-modal />
        <x-update-modal-master-pelajaran />


    </div>
</x-app-layout>
