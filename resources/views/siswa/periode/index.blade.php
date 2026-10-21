<x-app-layout title="Periode" type="siswa">
    <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
        <div class="flex flex-col">
            <h1 class="text-3xl leading-9 font-semibold">
                Periode
            </h1>
            <span class="text-sm leading-5 font-medium">
                Informasi detail terkait periode yang berjalan
            </span>
        </div>

        <div class="flex flex-row items-center gap-4">
            <div
                class="p-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-200 transition-all duration-300 ease-in-out cursor-pointer">
                <img src="{{ asset('Icon/Bell_light.svg') }}" alt="notification icon" class="w-6 h-6" />
            </div>
            {{-- Dummy Data --}}
            @php
                $dummyName = 'Antonio Angelo';
                $initials = collect(explode(' ', $dummyName))
                    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
                    ->join('');
            @endphp
            <div
                class="flex flex-row items-center gap-2 px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 hover:bg-slate-200 transition-all duration-300 ease-in-out cursor-pointer">
                <div
                    class="p-1 w-7 h-7 rounded-full bg-black text-white flex items-center justify-center font-bold text-sm leading-4">
                    {{ $initials }}
                </div>
                <span class="text-xs leading-4 font-semibold">{{ $dummyName }}</span>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-4 p-8 gap-4">
        <a href="{{ url()->previous() }}"
            class="col-span-4 text-white text-sm leading-4 bg-black rounded-lg px-3 py-2 w-fit">
            Kembali
        </a>

        {{-- Period Card --}}
        <div class="col-span-4 flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $activePeriods[0]['nama_periode'] }}
                    </h1>
                    <span class="text-sm text-gray-500">
                        T.A {{ $activePeriods[0]['tahun_ajaran'] }}
                    </span>
                </div>
            </div>
            <div class="flex flex-row justify-between w-full">
                @php
                    $startDateFormated = new DateTimeImmutable($activePeriods[0]['tanggal_buka']);
                    $endDateFormated = new DateTimeImmutable($activePeriods[0]['tanggal_tutup']);
                    $data = [
                        'Status Pengumuman' =>
                            $activePeriods[0]['status_pengumuman'] != 'AKTIF' ? 'Sudah diumumkan' : 'Belum diumumkan',
                        'Tanggal Mulai' => $startDateFormated->format('d M y')
                            ? $startDateFormated->format('d M y')
                            : '-',
                        'Tanggal Selesai' => $endDateFormated->format('d M y')
                            ? $endDateFormated->format('d M y')
                            : '-',
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
        {{-- Daftar Kelas --}}
        <div class="flex flex-row justify-between items-end col-span-4">
            <div class="flex flex-col ">
                <h2 class="text-xl font-semibold">
                    Daftar Kelas
                </h2>
                <p>
                    Data kelas yang dapat dipilih oleh siswa
                </p>
            </div>

            <button class="text-sm bg-blue-700 w-fit py-1 px-3 text-white rounded-md hover:bg-gray-800">
                Pilih Kelas
            </button>
        </div>
        @foreach ($formattedMenus as $index => $menu)
            <x-kelas-card :item="$menu" />
        @endforeach


    </div>

</x-app-layout>
