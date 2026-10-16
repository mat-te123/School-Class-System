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
    <div class="grid grid-cols-4 p-8 gap-4">
        {{-- Student Card --}}
        <div class="col-span-3 flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $name }}
                    </h1>
                    <span class="text-sm text-gray-500">
                        NISN : - • NIS : -
                    </span>
                </div>
                <div class="flex flex-row gap-4">
                    <button class="text-sm bg-blue-700 w-fit py-1 px-3 text-white rounded-md hover:bg-gray-800">
                        lihat detail
                    </button>
                </div>
            </div>
            <div class="flex flex-row justify-between w-full">
                @php
                    $data = [
                        'Kelas' => '-',
                        'Jenis Kelamin' => '-',
                        'Angkatan' => '-',
                        'Status' => false ? 'Aktif' : 'Tidak Aktif',
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
        {{-- Reprt Card --}}
        <div class="col-span-1 flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4 justify-between">
            <div>
                <h1 class="text-2xl font-semibold leading-6">
                    Mengalami Kendala ?
                </h1>
                <span class="text-sm text-gray-500">
                    Silahkan hubungi admin untuk mendapatkan bantuan.
                </span>
            </div>
            <a href="#"
                class="bg-gradient-to-t from-[#273344] to-[#1a202c] text-white text-sm font-semibold py-2 px-4 rounded-lg w-full text-center">
                Hubungi Admin
            </a>
        </div>
        {{-- periode Hardcoded --}}
        <x-siswa-periode-data tahun_ajaran="2026/2027" />

    </div>



</x-app-layout>
