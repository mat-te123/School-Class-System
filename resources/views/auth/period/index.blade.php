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
            <h1 class="text-4xl leading-9 font-semibold">
                Periode
            </h1>
            <span class="text-md leading-5 font-medium">
                Kelola periode penjurusan
            </span>
        </div>
        <div class="flex flex-col ">

        </div>
        <div class="grid grid-cols-3 grid-rows-2 py-6 px-8 gap-3">
            <div class="col-span-3 row-span-2 flex flex-row justify-end w-full h-fit">
                {{-- <div class="flex flex-row gap-4">
                    <select id="fterbaru" class="px-1 bg-[#F9FAFB] border border-gray-400 rounded-lg text-gray-400">
                        <option value="terbaru">new</option>
                        <option value="terlama">old</option>
                    </select>
                    <select id="faktif" class="px-1 bg-[#F9FAFB] border border-gray-400 rounded-lg text-gray-400">
                        <option value="">Status : semua(aktif/tidak aktif)</option>
                        <option value="active">Status : aktif</option>
                        <option value="not active">Status : tidak aktif</option>
                    </select>
                </div> --}}


                <button
                    class="px-4 py-2 text-white rounded-md bg-[linear-gradient(180deg,#273344_11.77%,#000_166.84%)] whitespace-nowrap"
                    @click="
                    showaddmodal=true;
                    addUrl='{{ route('periode-penjurusan.store') }}';
                    ">
                    Tambah Periode
                </button>

            </div>
            @foreach ($periode as $item)
                @php
                    $tanggal_buka = new DateTimeImmutable($item->tanggal_buka);
                    $tanggal_tutup = new DateTimeImmutable($item->tanggal_tutup);
                    $nama_periode =
                        strlen($item->nama_periode) > 20
                            ? substr($item->nama_periode, 0, 20) . '...'
                            : $item->nama_periode;
                @endphp
                <x-period-card :nama_periode="$nama_periode" :tahun_ajaran="$item->tahun_ajaran" :tanggal_buka="$tanggal_buka->format('d F Y')" :tanggal_tutup="$tanggal_tutup->format('d F Y')" :is_active="$item->is_active"
                    :item="$item" />
            @endforeach

        </div>
        @if ($periode->lastPage() > 1)
            <div class="flex flex-row items-center justify-center gap-3 bg-white border border-gray-300 py-2 px-5 rounded-full w-fit mx-auto">
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
        <x-add-modal-periode />
        <x-update-modal-periode />
        <x-flash-message />
</x-app-layout>
