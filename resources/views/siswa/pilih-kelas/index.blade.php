<x-app-layout title="Pilih Kelas" type="siswa">
    <div x-data="{ showDeleteModal: false }" @keydown.escape.window="showDeleteModal = false">
        <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
            <div class="flex flex-col">
                <h1 class="text-3xl leading-9 font-semibold">
                    Pilih Kelas
                </h1>
                <span class="text-sm leading-5 font-medium text-slate-500">
                    pilihlah kelas berdasarkan minat dan keinginann ananda
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
        <div class="flex flex-col gap-4 py-6 px-8 w-1/2">
            <div class="flex flex-row justify-between items-baseline">
                <div class="flex flex-col gap-1">
                    <h2 class="text-xl leading-6 font-semibold">Pilihan Kelas</h2>
                    <p class="text-sm leading-5 font-medium text-slate-500">
                        Pilihlah kelas secara berurutan dengan pilihan pertama adalah prioritas pertama
                    </p>
                </div>
                {{-- <button type="button" @click="showDeleteModal = true" id="delete-botton"
                    class="bg-red-500 font-medium text-white py-2 px-4 text-sm whitespace-nowrap rounded-lg self-end">
                    Hapus pilihan
                </button> --}}
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
                    <div>
                        <label for="kelas{{ $i }}" class="block text-sm font-medium text-gray-700">Pilihan
                            {{ $i }}</label>
                        <select id="kelas{{ $i }}" name="pilihan[]" required
                            class="select-primary mt-1 block w-full">
                            <option value="">Pilih Kelas</option>
                            @foreach ($kelasTersedia as $kelas)
                                <option value="{{ $kelas->id }}" @selected(old('pilihan.' . $pilihanIndex, $pilihanSaatIni[$i] ?? null) === $kelas->id)>
                                    {{ $kelas->nama_menu }}{{ $kelas->rumpun ? " ({$kelas->rumpun})" : '' }}
                                </option>
                            @endforeach
                        </select>
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
        {{-- delete modal --}}
        <div x-show="showDeleteModal" x-cloak x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.outside="showDeleteModal = false"
                class="bg-white rounded-xl flex flex-col gap-4 p-6 w-full max-w-md shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900">Konfirmasi hapus pilihan</h3>
                <p class="text-sm text-gray-600">
                    Apakah Anda yakin ingin menghapus semua pilihan kelas?
                </p>
                <form method="POST" action="{{ route('siswa.pilih-kelas.cancel') }}" class="flex justify-end gap-2">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="showDeleteModal = false"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg">
                        Hapus
                    </button>
                </form>
            </div>
        </div>

        @push('scripts')
            <script src="{{ asset('js/ClassSelection.js') }}"></script>
        @endpush
    </div>
</x-app-layout>
