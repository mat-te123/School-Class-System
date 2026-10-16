<x-app-layout title="Detail Siswa">
    <div x-data="{
        showDeleteModal: false,
        confirmationData: '',
        deleteUrl: '',
    }" class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="text-4xl leading-9 font-semibold">
                Siswa > <span class="text-2xl text-gray-500 lowercase">
                    {{ $siswa->nama_lengkap }}
                </span>
            </h1>
            <span class="text-md leading-5 font-medium">
                Kelola data pribadi siswa beserta nilai siswa
            </span>
        </div>

        <div class="flex flex-col py-6 px-8 gap-4">
            <a href="{{ route('admin-siswa.index') }}"
                class="bg-gradient-to-t from-[#273344] to-[#1a202c] text-white text-sm font-semibold py-2 px-4 rounded-lg w-fit">
                Kembali
            </a>
            <div class="flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
                <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-2xl font-semibold leading-6">
                            {{ $siswa->nama_lengkap }}
                        </h1>
                        <span class="text-sm text-gray-500">
                            NISN : {{ $siswa->nisn }} • NIS : {{ $siswa->nis }}
                        </span>
                    </div>
                    <div class="flex flex-row gap-4">
                        <button class="text-sm bg-gray-900 w-fit py-1 px-3 text-white rounded-lg hover:bg-gray-800">
                            edit
                        </button>
                        <button
                            @click=" 
                        showDeleteModal = true; 
                        confirmationData='{{ $siswa->nama_lengkap }}';
                        deleteUrl = '{{ route('siswa.destroy', $siswa->id) }}';

                        "
                            class="text-sm bg-red-600 w-fit py-1 px-3 text-white rounded-lg hover:bg-red-500">
                            delete
                        </button>
                    </div>
                </div>
                <div class="flex flex-row justify-between w-full">
                    @php
                        $data = [
                            'Kelas' => $siswa->KelasAsalRelation ? $siswa->KelasAsalRelation->nama_kelas : '-',
                            'Jenis Kelamin' => $siswa->jenis_kelamin ? $siswa->jenis_kelamin : '-',
                            'Angkatan' => $siswa->angkatan ? $siswa->angkatan : '-',
                            'Status' => $siswa->is_active ? 'Aktif' : 'Tidak Aktif',
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

        <x-nilai-siswa-profile :siswa-id="$siswa->id" />

        <x-delete-modal />
</x-app-layout>
