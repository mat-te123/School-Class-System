<x-app-layout title="Pilih Kelas" type="siswa">
    <div class="flex flex-row py-6 px-8 justify-between items-center  border-b border-black">
        <div class="flex flex-col">
            <h1 class="text-3xl leading-9 font-semibold">
                Pilih Kelas
            </h1>
            <span class="text-sm leading-5 font-medium">
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
    <pre>
        {{ $pendaftaran }}
    </pre>

</x-app-layout>
