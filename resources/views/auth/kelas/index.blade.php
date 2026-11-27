<x-app-layout title="Kelas">
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
                Kelas
            </h1>
            <span class="subTitle">
                Kelola kelas pada sistem
            </span>
        </div>

        <div class="grid grid-cols-4 py-6 px-8 gap-6">
            <div class="flex flex-row justify-between items-end col-span-4 gap-3">
                <div class="flex flex-col ">
                    <h2 class="mainTitle text-lg">
                        Daftar Kelas
                    </h2>
                    <span class="subTitle">
                        Daftar kelas yang tersedia pada sistem
                    </span>
                </div>
                <div class="flex flex-row justify-between items-end gap-3 ">
                    <form method="GET" action="{{ route('admin-paket-menu.index') }}">
                        @php
                            $selectedPeriodeId = request()->get('periode_id', $periodes->first()?->id);

                            $selectedPeriode = $periodes->firstWhere('id', $selectedPeriodeId);

                            $isPeriodeActive = $selectedPeriode ? (bool) $selectedPeriode->is_active : false;
                        @endphp
                        @if ($periodes->isEmpty())
                            <button class="select-primary text-sm" disabled>
                                Tidak ada periode aktif
                            </button>
                        @else
                            <select name="periode_id" id="periode_id" onchange="this.form.submit()"
                                class="select-primary text-sm">
                                <option value= "">pilih periode </option>
                                @foreach ($periodes as $periode)
                                    <option value="{{ $periode->id }}" @selected($periode->id == $selectedPeriodeId)>
                                        Tahun Ajaran : {{ $periode->tahun_ajaran }}
                                    </option>
                                @endforeach
                            </select>

                        @endif

                    </form>

                    @if ($isPeriodeActive)
                        <button
                            class="text-sm px-4 py-2 text-white rounded-md bg-[linear-gradient(180deg,#273344_11.77%,#000_166.84%)] whitespace-nowrap"
                            @click="
                showaddmodal=true;
                addUrl='{{ route('paket-menu.store') }}';
            ">
                            Tambah Kelas
                        </button>
                    @else
                        <button type="button" disabled title="Periode yang dipilih sedang tidak aktif"
                            class="text-sm px-4 py-2 text-gray-500 bg-gray-300 rounded-md cursor-not-allowed whitespace-nowrap">
                            Tambah Kelas
                        </button>
                    @endif
                </div>
            </div>
            @if ($paginator->isEmpty())
                <div
                    class="col-span-4 flex flex-col items-center justify-center py-12 px-2 bg-gray-50 border border-gray-300 rounded-lg">
                    @if ($periodes->isEmpty())
                        <h3 class="mainTitle text-lg">
                            Tidak ada periode aktif pada sistem
                        </h3>
                        <p class="subTitle text-sm ">
                            silahkan <a target="_blank" href="{{ route('admin-periode-penjurusan.index') }}"
                                class="text-blue-400 hover:underline">tambahkan periode</a>
                        </p>
                    @else
                        <h3 class="mainTitle text-lg">
                            Tidak ada kelas yang terdaftar pada sistem
                        </h3>
                        <p class="subTitle text-sm ">
                            silahkan tambahkan kelas
                        </p>
                    @endif




                </div>
            @else
                @foreach ($paginator as $item)
                    <x-kelas-card :item="$item" />
                @endforeach

            @endif




        </div>

        <x-add-modal-kelas />
        <x-delete-modal />
        <x-update-modal-kelas />
    </div>
</x-app-layout>
