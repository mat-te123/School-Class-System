<div class="flex flex-col bg-white p-6 rounded-lg shadow-md border border-slate-200 mt-6 gap-8">
    <div class="flex flex-col p-4 bg-white border border-slate-200 rounded-lg gap-4 w-fit">
        <div class="flex flex-col border-l border-slate-200 pl-4 gap-2">
            <span class="text-gray-700">
                Rata Rata Nilai Siswa
            </span>
            <span class="text-md leading-5 font-medium">
                {{ $rata_rata }}
            </span>

        </div>
    </div>

    @if ($nilaiData->isEmpty())
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 text-sm">
            Belum ada data nilai untuk siswa ini.
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr
                        class="bg-[#D1D5DB] text-slate-700 font-semibold border-b border-slate-200 first:[&_th]:rounded-l-xl first:[&_th]:w-0 last:[&_th]:rounded-r-xl">
                        <th class="p-3">no</th>
                        <th class="p-3">Mata Pelajaran</th>
                        <th class="p-3">Nilai</th>
                        <th class="p-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($nilaiData as $index => $item)
                        <tr class="hover:bg-slate-50">
                            <td class="p-3 font-medium text-slate-800">
                                {{ $index + 1 }}
                            </td>
                            <td class="p-3 text-slate-600">
                                {{ $item->mataPelajaran?->nama_mapel ?? '-' }}
                            </td>
                            <td class="p-3 font-semibold text-slate-900">
                                {{ $item->nilai_angka ?? '-' }}
                            </td>
                            <td class="p-3 font-semibold text-slate-900">
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
