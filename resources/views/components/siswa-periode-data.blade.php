<div class="col-span-2 flex flex-col gap-4">
    <div>
        <h2>
            Periode pendaftaran
        </h2>
        <span class="text-sm text-gray-500">
            lihat daftar periode pendaftaran yang dapat anda daftarkan
        </span>
    </div>
    <div class="grid-span-3 bg-gray-50 border border-gray-300 p-3 rounded-lg flex flex-col gap-2" x-data="{ showeditdeletepopup: false, }">
        <div class="flex flex-row w-full justify-between relative">
            <div class="flex flex-col gap-1">
                <h2 class="text-lg leading-7 font-bold">
                    {{ $DataPeriode->nama_periode }}
                </h2>
                <span class="text-xs leading-4 font-medium">
                    T.A {{ $DataPeriode->tahun_ajaran }}
                </span>
            </div>
        </div>

        @if ($DataPeriode->is_active)
            <span class="text-xs leading-4 font-semibold text-green-600 px-3 py-1 rounded-lg bg-green-50 w-fit">
                Aktif
            </span>
        @else
            <span class="text-xs leading-4 font-semibold text-red-600 px-3 py-1 rounded-lg bg-red-50 w-fit">
                Tidak Aktif
            </span>
        @endif
        @php
            $tanggalBuka = new DateTimeImmutable($DataPeriode->tanggal_buka);
            $tanggalTutup = new DateTimeImmutable($DataPeriode->tanggal_tutup);
        @endphp
        <span class="text-xs leading-4 flex flex-row gap-2 ">
            <img src="{{ asset('Icon/Date_range.svg') }}" />
            {{ $tanggalBuka->format('d M Y') }} - {{ $tanggalTutup->format('d M Y') }}
        </span>
        <div class="flex flex-row w-full justify-end border-t border-gray-300 pt-2 gap-3">
            <a href="#" class="text-center text-sm leading-4 w-full border bg-gray-950 rounded-md text-white p-2">
                Lihat Detail
            </a>
        </div>


    </div>
</div>
