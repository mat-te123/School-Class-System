<x-app-layout title="Detail Siswa">
    <div x-data="{
        showDeleteModal: false,
        showupdateModal: false,
        showNilaiUpdateModal: false,
        confirmationData: '',
        deleteUrl: '',
        editUrl: '',
        nilaieditUrl: '',
        studentData: {},
        nilaiData: {},
        currentMenu: 'Nilai Siswa',
        menuItem: ['Nilai Siswa', 'Pilihan Siswa']
    }" class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="mainTitle">
                Siswa > <span class="text-2xl text-gray-500 lowercase">
                    {{ $siswa->nama_lengkap }}
                </span>
            </h1>
            <span class="subTitle">
                Kelola data pribadi siswa
            </span>
        </div>

        <div class="flex flex-col pt-6 px-8 gap-4 relative">
            <a href="{{ route('admin-siswa.index') }}" class="primary-button w-fit">
                Kembali
            </a>
            {{-- Data Card --}}
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
                        <button type="button" class="negative-primary-button"
                            @click="
                                showupdateModal = true;
                                studentData = {{ Js::from($siswa) }};
                                editUrl = '{{ route('siswa.update', $siswa->id) }}';
                            ">
                            edit
                        </button>
                        <button
                            @click=" 
                        showDeleteModal = true; 
                        confirmationData='{{ $siswa->nama_lengkap }}';
                        deleteUrl = '{{ route('siswa.destroy', ['id' => $siswa->id, 'isProfile' => 1]) }}';

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

            <div class="flex flex-row gap-4 " x-effect="() => {console.log('Current Menu:', currentMenu)}">
                <template x-for="item in menuItem" :key="item">
                    <button @click="currentMenu = item" :class="currentMenu === item ? 'border-b border-black' : ''"
                        class="px-4 font-semibold transition-all duration-200 ease-in-out hover:border-b hover:border-gray-400">
                        <span x-text="item"></span>
                    </button>
                </template>
            </div>
        </div>
        <div class="flex-1">
            <x-nilai-siswa-profile x-show="currentMenu === 'Nilai Siswa'" x-cloak :siswa-id="$siswa->id" />
            <x-pilihan-siswa-profile x-show="currentMenu === 'Pilihan Siswa'" x-cloak :siswa-id="$siswa->id"
                :isSiswaLoggedIn="true" />

        </div>

        <x-delete-modal />
        <x-update-modal-siswa :kelas-asal="$kelasAsal" />
        <x-update-modal-nilai-siswa />
    </div>
</x-app-layout>
