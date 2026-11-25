<x-app-layout title="Period">
    <div x-data="{
        showDeleteModal: false,
        showupdateModal: false,
        showaddmodal: false,
        confirmationData: '',
        addUrl: '',
        deleteUrl: '',
        updateUrl: '',
        Data: {},
    }">
        <div class="flex flex-col py-6 px-8 border border-b-black">
            <h1 class="mainTitle">
                Periode
            </h1>
            <span class="subTitle">
                Kelola periode penjurusan
            </span>
        </div>
        <div class="flex flex-col ">

        </div>
        <div class="grid grid-cols-3 py-6 px-8 gap-7">
            <div class="h-fit col-span-3 flex flex-row justify-between items-center">
                <div class="flex flex-col">
                    <h2 class="mainTitle text-lg">
                        Daftar periode yang terdaftar pada sistem
                    </h2>
                    <span class="subTitle text-sm">
                        Berikut adalah daftar periode yang telah terdaftar pada sistem. Anda dapat menambahkan,
                        mengedit,
                        atau menghapus periode sesuai kebutuhan.
                    </span>
                </div>
                @if ($canAddPeriod)
                    <button class="primary-button whitespace-nowrap w-fit"
                        @click="
                    showaddmodal=true;
                    addUrl='{{ route('periode-penjurusan.store') }}';
                    ">
                        Tambah Periode
                    </button>
                @else
                    <button type="button" disabled
                        title="Periode tahun ajaran {{ $tahunAjaranSekarang }} sudah tersedia"
                        class="primary-button disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap w-fit">
                        Tambah Periode
                    </button>
                @endif
            </div>
            @foreach ($periode as $item)
                @php
                    $tanggal_buka = new DateTimeImmutable($item->tanggal_buka);
                    $tanggal_tutup = new DateTimeImmutable($item->tanggal_tutup);
                    $nama_periode =
                        strlen($item->nama_periode) > 20
                            ? substr($item->nama_periode, 0, 20) . '...'
                            : $item->nama_periode;
                    $isPublished = $item->isPengumumanDibuka();
                @endphp
                <x-period-card :nama_periode="$nama_periode" :tahun_ajaran="$item->tahun_ajaran" :tanggal_buka="$tanggal_buka->format('d F Y')" :tanggal_tutup="$tanggal_tutup->format('d F Y')" :is_active="$item->is_active"
                    :item="$item" :is_published="$isPublished" />
            @endforeach

        </div>
        @if ($periode->lastPage() > 1)
            <div
                class="flex flex-row items-center justify-center gap-3 bg-white border border-gray-300 py-2 px-5 rounded-full w-fit mx-auto">
                @if ($periode->onFirstPage())
                    <span class="text-gray-400 cursor-not-allowed">&lt; sebelum</span>
                @else
                    <a href="{{ $periode->previousPageUrl() }}" class="text-gray-800  hover:underline">&lt;
                        sebelum</a>
                @endif
                <div class="flex flex-row gap-2 ">
                    @for ($halaman = 1; $halaman <= $periode->lastPage(); $halaman++)
                        <a href="{{ $periode->url($halaman) }}"
                            class="rounded {{ $halaman == $periode->currentPage() ? ' text-gray-800 font-bold' : 'text-gray-500 hover:text-gray-800 font-bold' }}">
                            {{ $halaman }}
                        </a>
                    @endfor
                </div>
                @if ($periode->hasMorePages())
                    <a href="{{ $periode->nextPageUrl() }}" class="text-gray-800 hover:underline">selanjutnya
                        &gt;</a>
                @else
                    <span class="text-gray-500 cursor-not-allowed">selanjutnya &gt;</span>
                @endif
            </div>
        @endif

        <x-delete-modal />
        @if ($canAddPeriod)
            <x-add-modal-periode :tahunAjaranSekarang="$tahunAjaranSekarang" />
        @endif
        <x-update-modal-periode />

</x-app-layout>
