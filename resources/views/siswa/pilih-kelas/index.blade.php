<x-app-layout title="Pilih Kelas" type="siswa">
    @php
        $classDetails = $kelasTersedia->mapWithKeys(function ($kelas) {
            return [
                $kelas->id => [
                    'nama_menu' => $kelas->nama_menu,
                    'rumpun' => $kelas->rumpun,
                    'kuota_kapasitas' => $kelas->kuota_kapasitas,
                    'kuota_tersisa' => $kelas->kuota_tersisa,
                    'kriteria_bobot' => $kelas->kriteriaBobots
                        ->map(function ($bobot) {
                            return [
                                'id' => $bobot->id,
                                'nama_mapel' => $bobot->mataPelajaran?->nama_mapel ?? 'N/A',
                                'kode_mapel' => $bobot->mataPelajaran?->kode_mapel ?? 'N/A',
                                'bobot_persen' => round((float) $bobot->bobot_persen, 2),
                            ];
                        })
                        ->values(),
                ],
            ];
        });
    @endphp
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <div x-data="{
        showDeleteModal: false,
        showDetailPage: false,
        selectedChoiceIds: {},
        selectedClassDetail: null,
        classDetails: @js($classDetails),
        openClassDetail(selectId) {
            const id = this.selectedChoiceIds[selectId];
            if (!id) return;
    
            this.showDetailPage = true;
            this.selectedClassDetail = this.classDetails[id] || null;
        }
    }" @keydown.escape.window="showDeleteModal = false; showDetailPage = false">
        <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
            <div class="flex flex-col">
                <h1 class="text-3xl leading-9 font-semibold">
                    Pilih Kelas
                </h1>
                <span class="text-sm leading-5 font-medium text-slate-500">
                    pilihlah kelas berdasarkan minat dan keinginann ananda
                </span>
            </div>

        </div>
        <div class="flex flex-col gap-4 py-6 px-8 w-1/2">
            <div class="flex flex-row justify-between items-baseline">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl leading-6 font-semibold">Pilihan Kelas</h2>
                    <p class="text-sm leading-5 font-medium text-slate-500">
                        Pilihlah kelas secara berurutan dengan pilihan pertama adalah prioritas pertama
                    </p>
                </div>
            </div>

            @error('pilihan')
                <p role="alert" class="text-sm font-medium text-red-600">{{ $message }}</p>
            @enderror
            <form
                action="{{ empty($pendaftaran) ? route('siswa.pilih-kelas.store') : route('siswa.pilih-kelas.update') }}"
                method="POST" class="flex flex-col gap-4"
                data-has-submission="{{ !empty($pendaftaran) ? 'true' : 'false' }}">
                @csrf
                @if (!empty($pendaftaran))
                    @method('PUT')
                @endif

                @php
                    $pilihanSaatIni =
                        $pendaftaran?->detailPendaftaran->pluck('paket_menu_pilihan_id', 'urutan_pilihan')->all() ?? [];
                @endphp
                @for ($i = 1; $i <= $siswaMaxChoose; $i++)
                    @php($pilihanIndex = $i - 1)
                    <div class="flex flex-row gap-2 items-center">
                        <div class="flex flex-col gap-1 w-full">
                            <label for="kelas{{ $i }}"
                                class="block text-sm font-medium text-gray-700">Pilihan
                                {{ $i }} <span class="text-red-500"> * </span></label>
                            <div class="flex flex-row gap-2 items-end">
                                <select id="kelas{{ $i }}" name="pilihan[]" required x-init="selectedChoiceIds['kelas{{ $i }}'] = $el.value"
                                    @change="selectedChoiceIds['kelas{{ $i }}'] = $el.value"
                                    class="select-primary mt-1 block w-full">
                                    <option value="">Pilih Kelas</option>
                                    @foreach ($kelasTersedia as $kelas)
                                        <option value="{{ $kelas->id }}" @selected(old('pilihan.' . $pilihanIndex, $pilihanSaatIni[$i] ?? null) === $kelas->id)>
                                            {{ $kelas->nama_menu }}{{ $kelas->rumpun ? " ({$kelas->rumpun})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button x-show="selectedChoiceIds['kelas{{ $i }}']" x-cloak
                                    @click="openClassDetail('kelas{{ $i }}')" type="button"
                                    aria-label="Lihat detail kelas pilihan {{ $i }}"
                                    class="negative-primary-button flex h-10.5 w-fit items-center justify-center gap-2 whitespace-nowrap">
                                    <span class="hidden sm:inline">Detail kelas</span>
                                    <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow Down" class="w-4 h-4">
                                </button>
                            </div>

                        </div>

                    </div>
                @endfor
                <div class="flex flex-col gap-2">
                    <div class="flex flex-row gap-4 mt-4 justify-end">
                        <a class="negative-primary-button w-fit" href="{{ route('siswa.dashboard') }}">Kembali</a>
                        @if (empty($pendaftaran))
                            <button class="primary-button w-fit px-5!" type="submit">Kirim</button>
                        @else
                            <button class="primary-button w-fit px-5!" type="submit">Perbarui pilihan</button>
                        @endif

                    </div>
                    <span id="no-change-hint" class="text-xs text-red-500 hidden text-right"> * Anda belum melakukan
                        perubahan
                        pilihan.
                    </span>
                </div>


            </form>
        </div>

        @push('scripts')
            <script src="{{ asset('js/ClassSelection.js') }}"></script>
        @endpush
        <div x-show="showDetailPage" x-cloak class="fixed inset-0 z-40 bg-slate-950/30"
            @click.self="showDetailPage = false">
            <aside role="dialog" aria-modal="true" aria-labelledby="class-detail-title"
                class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col border-l border-gray-300 bg-white shadow-xl"
                x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full"
                x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" x-cloak
                x-show="showDetailPage">
                <header class="flex items-start justify-between border-b border-gray-200 px-5 py-4">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Informasi kelas</p>
                        <h2 id="class-detail-title" class="mt-1 text-lg font-semibold text-gray-950">
                            Detail Kelas
                        </h2>
                    </div>
                    <button type="button" @click="showDetailPage = false"
                        class="flex size-9 items-center justify-center rounded-lg border border-gray-300 text-xl leading-none text-gray-600 hover:bg-gray-50"
                        aria-label="Tutup detail kelas">&times;</button>
                </header>

                <div class="flex-1 overflow-y-auto px-5 py-5">
                    <div x-show="selectedClassDetail" x-cloak class="flex flex-col gap-5">
                        <div>
                            <p class="text-sm text-gray-500"
                                x-text="selectedClassDetail?.rumpun || 'Rumpun belum ditentukan'"></p>
                            <h3 class="mt-1 text-xl font-semibold text-gray-950"
                                x-text="selectedClassDetail?.nama_menu"></h3>
                        </div>

                        <dl
                            class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-gray-200 bg-gray-200">
                            <div class="bg-white p-4">
                                <dt class="text-xs font-medium text-gray-500">Kapasitas</dt>
                                <dd class="mt-1 text-lg font-semibold"
                                    x-text="selectedClassDetail?.kuota_kapasitas ?? '-'"></dd>
                            </div>
                            <div class="bg-white p-4">
                                <dt class="text-xs font-medium text-gray-500">Sisa kuota</dt>
                                <dd class="mt-1 text-lg font-semibold"
                                    x-text="selectedClassDetail?.kuota_tersisa ?? '-'"></dd>
                            </div>
                        </dl>

                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Mata pelajaran penentu</h4>
                            <ul class="mt-2 divide-y divide-gray-200 rounded-lg border border-gray-200">
                                <template x-for="subject in (selectedClassDetail?.kriteria_bobot || [])"
                                    :key="subject.id">
                                    <li class="flex items-center justify-between gap-4 px-4 py-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900" x-text="subject.nama_mapel">
                                            </p>
                                            <p class="text-xs text-gray-500" x-text="subject.kode_mapel"></p>
                                        </div>
                                        <span class="shrink-0 text-sm font-semibold text-gray-800"
                                            x-text="subject.bobot_persen + '%'"></span>
                                    </li>
                                </template>
                                <li x-show="(selectedClassDetail?.kriteria_bobot || []).length === 0"
                                    class="px-4 py-3 text-sm text-gray-500">
                                    Belum ada bobot mata pelajaran.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <footer class="border-t border-gray-200 px-5 py-4">
                    <button type="button" @click="showDetailPage = false" class="negative-primary-button w-full">
                        Tutup
                    </button>
                </footer>
            </aside>
        </div>
    </div>

</x-app-layout>
