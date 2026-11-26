<x-app-layout title="Periode" type="siswa">
    <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
        <div class="flex flex-col">
            <h1 class="mainTitle">
                Periode
            </h1>
            <span class="subTitle">
                Informasi detail terkait periode yang berjalan
            </span>
        </div>
    </div>
    <div class="grid grid-cols-4 p-8 gap-4">
        <a href="{{ route('siswa.dashboard') }}" class="back-button">
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
                            $activePeriods[0]['status_pengumuman'] == 'AKTIF' ? 'Sudah diumumkan' : 'Belum diumumkan',
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
                <h2 class="mainTitle">
                    Daftar Kelas
                </h2>
                <p class="subTitle">
                    Data kelas yang dapat dipilih oleh siswa
                </p>
            </div>
            @if ($isAlreadyAppyClasses)
                <div class="flex flex-row gap-4">
                    <a href="{{ route('siswa.profile') }}" class="negative-primary-button w-fit">
                        Cek Pilihan Anda
                    </a>
                    @if ($data['Status Pengumuman'] == 'Sudah diumumkan')
                        <button type="button" disable class="primary-button w-fit cursor-not-allowed opacity-50">
                            Perbarui Pilihan
                        </button>
                    @else
                        <a href="{{ route('siswa.pendaftaran-pilihan.index') }}" class="primary-button w-fit">
                            Perbarui Pilihan
                        </a>
                    @endif

                </div>
            @else
                @if ($data['Status Pengumuman'] == 'Sudah diumumkan')
                    <button type="button" disable class="primary-button w-fit cursor-not-allowed">
                        Pilih Kelas
                    </button>
                @else
                    <a href="{{ route('siswa.pendaftaran-pilihan.index') }}" class="primary-button w-fit">
                        Pilih Kelas
                    </a>
                @endif

            @endif

        </div>
        @foreach ($formattedMenus as $index => $menu)
            <x-kelas-card :item="$menu" :isSiswa="true" />
        @endforeach
    </div>

</x-app-layout>
