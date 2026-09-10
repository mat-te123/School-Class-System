<x-app-layout title="Period">
    <div x-data="{
        showDeleteModal: false,
        showupdateModal: false,
        showaddmodal: false,
        confirmationData: '',
        addUrl: '',
        deleteUrl: '',
        editUrl: '',
        periodeData: {},
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
            <div class="col-span-3 row-span-2 flex flex-row justify-between w-full h-fit">
                <div class="flex flex-row gap-4">
                    <select id="fterbaru" class="px-2 bg-[#F9FAFB] border border-gray-400 rounded-lg text-gray-400">
                        <option value="terbaru">terbaru</option>
                        <option value="terlama">terlama</option>
                    </select>
                    <select id="faktif" class="px-2 bg-[#F9FAFB] border border-gray-400 rounded-lg text-gray-400">
                        <option value="active">aktif</option>
                        <option value="not active">tidak aktif</option>
                    </select>
                    <button class="px-2 bg-[#F9FAFB] border border-gray-400 rounded-lg text-gray-400">
                        select date
                    </button>
                </div>


                <button
                    class="px-4 py-2 text-white rounded-md bg-[linear-gradient(180deg,#273344_11.77%,#000_166.84%)] whitespace-nowrap"
                    @click="
                    showaddmodal=true;
                    addUrl='{{ route('periode-penjurusan.store') }}';
                    ">
                    Tambah Periode
                </button>

            </div>
            @for ($i = 0; $i <= 2; $i++)
                @foreach ($periode as $item)
                    @php
                        $tanggal_buka = new DateTimeImmutable($item->tanggal_buka);
                        $tanggal_tutup = new DateTimeImmutable($item->tanggal_tutup);
                        $nama_periode =
                            strlen($item->nama_periode) > 20
                                ? substr($item->nama_periode, 0, 20) . '...'
                                : $item->nama_periode;
                    @endphp
                    <x-period-card :nama_periode="$nama_periode" :tahun_ajaran="$item->tahun_ajaran" :tanggal_buka="$tanggal_buka->format('d F Y')" :tanggal_tutup="$tanggal_tutup->format('d F Y')"
                        :is_active="$item->is_active" :item="$item" />
                @endforeach
            @endfor



        </div>

        <div>
            <p>
                {{ json_encode($periode, JSON_PRETTY_PRINT) }}
            </p>
        </div>
        <x-delete-modal />
        <x-add-modal-periode />
        <x-update-modal-periode />
        <x-flash-message />
</x-app-layout>
