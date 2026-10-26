<x-app-layout title="Pilih Kelas" type="siswa">
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
    <div class="flex flex-col gap-4 py-6 px-8">
        <div class="flex flex-col gap-1">
            <h2 class="text-xl leading-6 font-semibold">Pilihan Kelas</h2>
            <p class="text-sm leading-5 font-medium text-slate-500">
                Pilihlah kelas secara berurutan dengan pilihan pertama adalah prioritas pertama
            </p>
        </div>
        @error('pilihan')
            <p role="alert" class="text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
        <form action="{{ route('siswa.pilih-kelas.store') }}" method="POST" class="flex flex-col gap-4 w-1/2">
            @csrf
            @php
                $maxStudentChoice = $pendaftaran?->periodePendaftaran?->max_pilihan_siswa ?? 3;
                $pilihanSaatIni = $pendaftaran?->detailPendaftaran
                    ->pluck('paket_menu_pilihan_id', 'urutan_pilihan')
                    ->all() ?? [];
            @endphp
            @for ($i = 1; $i <= $maxStudentChoice; $i++)
                @php($pilihanIndex = $i - 1)
                <div>
                    <label for="kelas{{ $i }}" class="block text-sm font-medium text-gray-700">Pilihan
                        {{ $i }}</label>
                    <select id="kelas{{ $i }}" name="pilihan[]" required class="select-primary mt-1 block w-full">
                        <option value="">Pilih Kelas</option>
                        @foreach ($kelasTersedia as $kelas)
                            <option value="{{ $kelas->id }}" @selected(old('pilihan.'.$pilihanIndex, $pilihanSaatIni[$i] ?? null) === $kelas->id)>
                                {{ $kelas->nama_menu }}{{ $kelas->rumpun ? " ({$kelas->rumpun})" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endfor
            <div class="flex flex-row gap-4 mt-4 justify-end">
                <a href="{{ url()->previous() }}">Kembali</a>
                <button type="submit">Kirim</button>
            </div>
        </form>

    </div>
    @push('scripts')
        <script src="{{ asset('js/ClassSelection.js') }}"></script>
    @endpush
</x-app-layout>
