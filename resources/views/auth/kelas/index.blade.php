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
                <h1 class="text-4xl leading-9 font-semibold">
                    Kelas
                </h1>
                <span class="text-md leading-5 font-medium">
                    Kelola kelas pada sistem
                </span>
            </div>

            <div class="grid grid-cols-4 py-6 px-8 gap-4">
                <div class="flex flex-row justify-between items-start col-span-4 gap-3">
                    <div class="flex flex-col ">
                        <h2 class="text-lg leading-6 font-semibold">
                            Data Kelas • T.A {{ $periodes->first()?->tahun_ajaran }}
                        </h2>
                        <span class="text-sm font-medium leading-4">
                            Daftar kelas yang tersedia pada sistem
                        </span>
                    </div>
                    <div class="flex flex-row justify-between items-end gap-3">
                        <form method="GET" action="{{ route('admin-paket-menu.index') }}">
                            @php
                                $selectedPeriodeId = request()->get('periode_id', $periodes->first()?->id);
                            @endphp
                            <select name="periode_id" id="periode_id" onchange="this.form.submit()"
                                class="select-primary text-sm">
                                @foreach ($periodes as $periode)
                                    <option value="{{ $periode->id }}" @selected($periode->id == $selectedPeriodeId)>
                                        Tahun Ajaran : {{ $periode->tahun_ajaran }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                        <button
                            class="text-sm px-4 py-2 text-white rounded-md bg-[linear-gradient(180deg,#273344_11.77%,#000_166.84%)] whitespace-nowrap"
                            @click="
                    showaddmodal=true;
                    addUrl='{{ route('paket-menu.store') }}';
                    ">
                            Tambah Kelas
                        </button>
                    </div>
                </div>

                @foreach ($paginator as $item)
                    <x-kelas-card :item="$item" />
                @endforeach

            </div>

            <x-add-modal-kelas />
            <x-delete-modal />
            <x-update-modal-kelas />
            <x-flash-message />


    </x-app-layout>
