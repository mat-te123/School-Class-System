<x-app-layout title="Admin Dashboard">
    {{-- Alpine State Container --}}
    <div x-data="{
        showDeleteModal: false,
        showUpdateModal: false,
        studentName: '',
        deleteUrl: '',
        editUrl: ''
    }">

        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="text-4xl leading-9 font-semibold">
                Siswa
            </h1>
            <span class="text-md leading-5 font-medium">
                Kelola data murid, NISN, dan alokasi kelas
            </span>
        </div>

        <div class="flex flex-col py-6 px-8 gap-4">
            <div class="flex flex-col">
                <h2 class="text-lg leading-6 font-semibold">
                    Data Siswa
                </h2>
                <span class="text-sm leading-4 font-medium">
                    Tampilkan Data siswa berdasarkan Tahun ajaran
                </span>
            </div>

            <div class="flex flex-row bg-gray-100 p-3 w-full border border-gray-400 rounded-lg gap-6">
                <input type="text" placeholder="Cari nama, NISN, atau NIS..."
                    class="w-full py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg">
                <select class="py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg">
                    <option>Kelas</option>
                </select>
                <select class="py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg">
                    <option>Kelamin</option>
                </select>
                <select class="py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg">
                    <option>Periode</option>
                </select>
                <button
                    class="px-4 py-2 text-white rounded-md bg-[linear-gradient(180deg,#273344_11.77%,#000_166.84%)] whitespace-nowrap">
                    Perbarui Data
                </button>
            </div>

            <div class="rounded-xl border-gray-200 border overflow-hidden shadow-lg">
                <table class="w-full">
                    <thead
                        class="bg-gray-300 text-xs font-semibold uppercase text-gray-600 border-gray-200 [&_th]:px-4 [&_th]:py-3 [&_th]:border-r [&_th]:border-gray-200 [&_th]:text-left last:[&_th]:border-r-0">
                        <tr>
                            <th class="flex justify-center">#</th>
                            <th>Nama Lengkap</th>
                            <th>NISN</th>
                            <th>NIS</th>
                            <th>KELAS</th>
                            <th>JENIS KELAMIN</th>
                            <th>ANGKATAN</th>
                            <th>AKSI</th>
                        </tr>
                    </thead>
                    <tbody
                        class="[&_td]:p-3 border-gray-200 [&_td]:border-t [&_td]:text-base [&_td]:leading-6 [&_td]:font-normal [&_td]:text-left">
                        @forelse ($siswa as $index => $item)
                            <tr class="bg-gray-50 hover:bg-gray-200 hover:cursor-pointer group">
                                <th class="border-r border-t border-gray-200 p-2">
                                    {{ $index + 1 }}
                                </th>
                                <td>
                                    <div class="flex flex-row items-center gap-2 lowercase">
                                        {{ $item->nama_lengkap }}
                                        <button type="button" class="opacity-0 group-hover:opacity-100">
                                            <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow icon"
                                                class="w-4 h-4">
                                        </button>
                                    </div>
                                </td>
                                <td>{{ $item->nisn }}</td>
                                <td>{{ $item->nis }}</td>
                                <td>{{ $item->kelas_asal }}</td>
                                <td>{{ $item->jenis_kelamin ?? '-' }}</td>
                                <td>{{ $item->angkatan }}</td>
                                <td>
                                    <div class="flex flex-row gap-5 items-center">
                                        {{-- Edit Button --}}
                                        <button type="button"
                                            @click="
                                            showUpdateModal = true;
                                            studentName = '{{ addslashes($item->nama_lengkap) }}'
                                            editUrl = '{{ route('admin-siswa.index', $item->id) }}';
                                        "
                                            class="p-1 bg-blue-50 hover:bg-blue-300 rounded-lg transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                            <img src="{{ asset('Icon/Edit.svg') }}" alt="edit icon">
                                        </button>

                                        {{-- Delete Trash Button (Triggers Modal) --}}
                                        <button type="button"
                                            @click="
                                                showDeleteModal = true;
                                                studentName = '{{ addslashes($item->nama_lengkap) }}';
                                                deleteUrl = '{{ route('siswa.destroy', $item->id ?? 1) }}';
                                            "
                                            class="p-1 bg-red-50 hover:bg-red-300 rounded-lg transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                            <img src="{{ asset('Icon/Trash.svg') }}" alt="trash icon">
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-6 text-gray-500">
                                    Tidak ada data siswa ditemukan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="py-3 px-8 bg-gray-300 w-full">
                    <p class="text-black text-sm leading-5">
                        Total siswa: {{ $siswa->count() }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Include Reusable Delete Modal Component --}}
        <x-delete-modal />
        <x-update-modal />

    </div>
</x-app-layout>
