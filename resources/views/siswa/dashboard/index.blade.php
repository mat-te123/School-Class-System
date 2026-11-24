<x-app-layout title="Selamat datang" type="siswa">

    <div class="flex flex-col py-6 px-8 border-b border-black">
        <h1 class="mainTitle">
            Selamat datang
        </h1>
        <span class="lowercase subTitle">{{ $siswa->nama_lengkap }}</span>
    </div>
    <div class="grid grid-cols-4 p-8 gap-4">

        @if ($isperiodeAktif)
            @if ($pertukaranDate)
                @php
                    $tanggalMulai = \Carbon\Carbon::parse($pertukaranDate->tanggal_mulai_pertukaran);
                    $tanggalSelesai = \Carbon\Carbon::parse($pertukaranDate->tanggal_selesai_pertukaran);
                @endphp
                <div class="col-span-4" x-data="{ showPertukaranNotice: true }" x-show="showPertukaranNotice"
                    x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-cloak>
                    <div class="flex flex-row items-center rounded-lg border border-blue-300 bg-blue-50 px-4 py-3 text-sm text-blue-900 gap-2"
                        role="status">
                        <span
                            class="p-1 border border-blue-300 rounded-lg hover:bg-blue-600 hover:text-white text-blue-900 cursor-pointer"
                            @click="showPertukaranNotice = false">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </span>
                        <div>
                            <span class="font-semibold">Periode pertukaran kelas sedang berlangsung!</span>
                            <span>Periode pertukaran kelas dimulai pada tanggal
                                {{ $tanggalMulai->translatedFormat('d F Y') }}
                                dan berakhir pada tanggal
                                {{ $tanggalSelesai->translatedFormat('d F Y') }}.
                            </span>
                        </div>
                        <a href="{{ route('siswa.pertukaran.index') }}"
                            class=" ml-auto whitespace-nowrap rounded-lg bg-blue-600 px-3 py-1 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            ajukan pertukaran
                        </a>

                    </div>
                </div>
            @endif
        @else
            @if (!$isperiodeAktif)
                <div class="col-span-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                    role="status">
                    Saat ini tidak ada periode pendaftaran yang aktif.
                </div>
            @endif
        @endif


        {{-- Student Card --}}
        <div class="col-span-4 flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $siswa->nama_lengkap }}
                    </h1>
                    <span class="text-sm text-gray-500">
                        NISN : {{ $siswa->nisn ? $siswa->nisn : '-' }} • NIS : {{ $siswa->nisn ? $siswa->nisn : '-' }}
                    </span>
                </div>
                <div class="flex flex-row gap-4">
                    <a href="{{ route('siswa.profile') }}" class="negative-primary-button w-fit whitespace-nowrap">
                        Cek profile
                    </a>
                </div>
            </div>
            <div class="flex flex-row justify-between w-full">
                @php
                    $angkatan = "{$siswa->angkatan}/" . ($siswa->angkatan + 1);
                    $data = [
                        'Kelas' => $siswa->kelasAsalRelation?->nama_kelas ?? '-',
                        'Jenis Kelamin' => $siswa->jenis_kelamin ? $siswa->jenis_kelamin : '-',
                        'Angkatan' => $angkatan ? $angkatan : '-',
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
        {{-- Reprt Card --}}
        {{-- <div class="col-span-1 flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4 justify-between">
            <div>
                <h1 class="text-2xl font-semibold leading-6">
                    Mengalami Kendala ?
                </h1>
                <span class="text-sm text-gray-500">
                    Silahkan hubungi admin untuk mendapatkan bantuan.
                </span>
            </div>
            <a href="#" class="primary-button">
                Hubungi Admin
            </a>
        </div> --}}
        {{-- periode Hardcoded --}}
        <x-siswa-periode-data tahun_ajaran="{{ $angkatan }}" />


    </div>




</x-app-layout>
