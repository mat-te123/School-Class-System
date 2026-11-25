<x-app-layout title="Atur Bobot Mata Pelajaran" type="admin">
    <div class="flex flex-col gap-6 px-8 py-6">
        <header>
            <a href="{{ $paket ? route('paket-menu.show', ['identifier' => $paket->id], false) : route('periode-penjurusan.show', ['id' => $periode->id]) }}" class="back-button">Kembali</a>
            <h1 class="mainTitle mt-4">Atur Bobot Mata Pelajaran</h1>
            <p class="subTitle">{{ $periode->nama_periode }} · {{ $periode->tahun_ajaran }}</p>
        </header>

        <nav aria-label="Paket kelas" class="flex flex-wrap gap-2">
            @forelse ($pakets as $item)
                @php
                    $total = round($item->kriteria_bobots_sum_bobot_persen ?? 0, 2);
                @endphp
                <a href="{{ route('kriteria-bobot.index', ['periode' => $periode->id, 'paket_id' => $item->id]) }}"
                    @if ($paket->is($item)) aria-current="page" @endif
                    class="rounded-lg border border-slate-300 px-4 py-2 hover:bg-slate-100 {{ $paket->is($item) ? 'bg-blue-50 ring-2 ring-blue-600' : 'bg-white' }}">
                    <span class="block font-semibold">{{ $item->nama_menu }}</span>
                    <span class="text-sm {{ $total == 100 ? 'text-green-700' : 'text-amber-800' }}">
                        {{ $total }}% · {{ $total == 100 ? 'Lengkap' : ($total < 100 ? 'Kurang dari 100%' : 'Melebihi 100%') }}
                    </span>
                </a>
            @empty
                <p>Belum ada paket kelas untuk periode ini.</p>
            @endforelse
        </nav>

        @if ($paket)
            <section class="flex flex-col gap-4 rounded-lg border border-slate-300 bg-white p-4">
                <h2 class="text-lg font-semibold">{{ $paket->nama_menu }}</h2>
                <p class="text-sm">Target total bobot: 100%. Jalankan Proses Data setelah mengubah bobot untuk memperbarui skor draf.</p>
                @if ($mapel->isEmpty())
                    <p>Belum ada mata pelajaran yang tersedia.</p>
                @else
                    <form method="POST" action="{{ route('kriteria-bobot.package.save', ['periode' => $periode->id, 'paket' => $paket->id]) }}"
                        class="grid gap-4" x-data="{ total: 0, hitung(form) {
                            this.total = Array.from(form.querySelectorAll('input[type=number]:enabled')).reduce((sum, input) => sum + Math.round((Number(input.value) || 0) * 100), 0) / 100;
                        } }" x-init="$nextTick(() => hitung($el))" @input="$nextTick(() => hitung($el))" @submit="
                            if ($el.querySelector('[data-existing]:disabled') && !confirm('Hapus bobot mata pelajaran yang dinonaktifkan?')) $event.preventDefault();">
                        @csrf
                        @method('PUT')
                        <p class="text-sm">Aktifkan switch mata pelajaran yang dipakai, lalu isi bobotnya. Mata pelajaran yang dinonaktifkan tidak ikut dihitung.</p>
                        @error('bobot')
                            <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
                        @enderror
                        <div class="divide-y divide-slate-200">
                            @foreach ($mapel as $item)
                                @php
                                    $field = 'bobot.'.$item->id;
                                    $value = old($field, $bobot->get($item->id));
                                    $dipakai = $value !== null || $errors->has($field);
                                @endphp
                                <div class="grid grid-cols-[minmax(0,1fr)_5rem] items-center gap-2 py-2 sm:grid-cols-[minmax(0,1fr)_7rem]"
                                    x-data="{ dipakai: @js($dipakai) }">
                                    <label class="flex min-w-0 cursor-pointer items-center gap-2 sm:gap-3">
                                        <input type="checkbox" role="switch" class="peer sr-only" x-model="dipakai" @checked($dipakai)
                                            aria-controls="bobot-{{ $item->id }}">
                                        <span aria-hidden="true" class="relative h-5 w-9 shrink-0 rounded-full bg-slate-500 after:absolute after:start-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:content-[''] peer-checked:bg-blue-700 peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600 peer-focus-visible:ring-offset-2"></span>
                                        <span class="min-w-0 break-words">{{ $item->nama_mapel }}</span>
                                    </label>
                                    <input type="hidden" name="bobot[{{ $item->id }}]" value="" :disabled="dipakai" @disabled($dipakai)>
                                    <input id="bobot-{{ $item->id }}" name="bobot[{{ $item->id }}]" type="number" min="0" max="100" step="0.01"
                                        value="{{ $value }}" aria-label="Bobot {{ $item->nama_mapel }} (persen)"
                                        :disabled="!dipakai" :required="dipakai" @disabled(! $dipakai) @if ($bobot->has($item->id)) data-existing @endif
                                        @error($field) aria-invalid="true" aria-describedby="error-{{ $item->id }}" @enderror
                                        class="w-full rounded-lg border border-slate-400 px-2 py-1 disabled:bg-slate-100 disabled:text-slate-500">
                                    @error($field)
                                        <p id="error-{{ $item->id }}" class="col-span-2 text-sm text-red-700">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-4">
                            <output aria-live="polite" class="font-semibold" :class="total !== 100 && 'text-amber-800'">
                                Total bobot: <span x-text="total">{{ round($bobot->sum(), 2) }}</span>%
                                <span x-text="total === 100 ? '· Lengkap' : total < 100 ? '· Kurang dari 100%' : '· Melebihi 100%'"></span>
                            </output>
                            <button type="submit" class="primary-button">Simpan Bobot Paket</button>
                        </div>
                    </form>
                @endif
            </section>
        @endif
    </div>
</x-app-layout>
