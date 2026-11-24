@props(['isSiswaLoggedIn' => false])
<div {{ $attributes->merge([
    'class' => 'flex flex-col bg-white p-6 border-t border-slate-200 gap-8 h-full',
]) }}>


    @if ($nilaiData->isEmpty())
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg text-slate-500 text-sm">
            Data nilai anda belum terinput pada sistem
        </div>
    @else
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
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>no</th>
                        <th>Mata Pelajaran</th>
                        <th>Nilai</th>
                        @if (!$isSiswaLoggedIn)
                            <th>Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($nilaiData as $index => $item)
                        <tr class="hover:bg-slate-50">
                            <td>
                                {{ $index + 1 }}
                            </td>
                            <td>
                                {{ $item->mataPelajaran?->nama_mapel ?? '-' }}
                            </td>
                            <td>
                                {{ $item->nilai_angka ?? '-' }}
                            </td>
                            @if (!$isSiswaLoggedIn)
                                <td>
                                    <button type="button"
                                        @click="
                                                showNilaiUpdateModal = true;
                                                nilaiData = {{ Js::from($item) }};
                                                nilaieditUrl = '{{ route('nilai-siswa.update', $item->id) }}';
                                        "
                                        class="p-1 bg-blue-50 hover:bg-blue-300 rounded-lg transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                        <img src="{{ asset('Icon/Edit.svg') }}" alt="edit icon">
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
