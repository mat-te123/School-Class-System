<x-app-layout title="Selamat datang" type="siswa">
    <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
        <h1 class="text-3xl leading-9 font-semibold">
            Selamat Datang, Alesha
        </h1>
        <div class="flex flex-row items-center gap-4">
            <div
                class="p-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-200 transition-all duration-300 ease-in-out cursor-pointer">
                <img src="{{ asset('Icon/Bell_light.svg') }}" alt="notification icon" class="w-6 h-6" />
            </div>
            @php
                $name = 'Antonio Ledger';
                $initials = collect(explode(' ', $name))->map(fn($word) => strtoupper(substr($word, 0, 1)))->join('');
            @endphp
            <div
                class="flex flex-row items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-200 transition-all duration-300 ease-in-out cursor-pointer">
                <div
                    class="p-1 w-7 h-7 rounded-full bg-black text-white flex items-center justify-center font-bold text-sm leading-4">
                    {{ $initials }}
                </div>
                <span class="text-xs leading-4 font-semibold">{{ $name }}</span>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-4 p-8">
        <div class="flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $siswa->nama_lengkap ? $siswa->nama_lengkap : '-' }}
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



</x-app-layout>
