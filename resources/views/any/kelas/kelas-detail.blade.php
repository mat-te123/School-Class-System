<x-app-layout title="Kelas {{ $paketMenu->nama_menu }}" type="{{ $isAdmin ? 'admin' : 'siswa' }}">

    <div class ="flex flex-col py-6 px-8 border border-b-black">
        <h1 class="mainTitle">
            {{ $paketMenu->nama_menu }}
        </h1>
        <span class="subTitle">
            Lihat detail kelas {{ $paketMenu->nama_menu }} dan mata pelajaran yang tersedia
        </span>
    </div>
    <div class="flex flex-col p-8 gap-4">
        <a href="{{ route('admin-paket-menu.index') }}" class="back-button">
            Kembali
        </a>
        {{-- Data Card --}}
        <div class="flex flex-col bg-[#F9FAFB] p-4 border border-[#D1D5DB] rounded-lg gap-4">
            <div class="flex flex-row justify-between items-center border-b border-gray-300 pb-2">
                <div class="flex flex-col gap-2">
                    <h1 class="text-2xl font-semibold leading-6">
                        {{ $paketMenu->nama_menu }}
                    </h1>
                    <span class="text-sm text-gray-500">
                        Rumpun {{ $paketMenu->rumpun }}
                    </span>
                </div>
            </div>
            <div class="flex flex-row justify-between w-full">
                @php
                    $data = [
                        'Kuota Kapasitas' => $paketMenu->kuota_kapasitas ? $paketMenu->kuota_kapasitas : '-',
                        'Kuota Terisi' => $paketMenu->kuota_terisi ? $paketMenu->kuota_terisi : '-',
                        'Status' => $paketMenu->is_active ? 'Aktif' : 'Tidak Aktif',
                    ];
                @endphp
                @foreach ($data as $key => $value)
                    <div class="flex flex-col gap-1 w-full">
                        <span class="text-sm text-gray-500">
                            {{ $key }}
                        </span>
                        <span class="text-base font-semibold">
                            {{ $value }}
                        </span>
                    </div>
                @endforeach

            </div>

        </div>
    </div>
    <div class="flex flex-col pb-6 px-8 gap-6">
        <div class="flex flex-row justify-between items-end">
            <div class="flex flex-col">
                <h2 class="mainTitle text-xl">
                    Daftar Mata Pelajaran
                </h2>
                <span class="subTitle">
                    Atur mata pelajaran yang digunakan dan bobot penilaian untuk kelas {{ $paketMenu->nama_menu }}.
                </span>
            </div>
        </div>

        @if ($isAdmin)
            @if ($mapel->isEmpty())
                <p class="rounded-lg border border-slate-300 bg-white p-4 text-sm">
                    Belum ada mata pelajaran aktif untuk diatur.
                </p>
            @else
                <form method="POST" action="{{ route('kriteria-bobot.package.save', ['paket' => $paketMenu->id]) }}"
                    class="grid gap-4" x-data="{
                        total: 0,
                        hitung(form) {
                            this.total = Array.from(form.querySelectorAll('input[type=number]:enabled'))
                                .reduce((sum, input) => sum + Math.round((Number(input.value) || 0) * 100), 0) / 100;
                        }
                    }" x-init="$nextTick(() => hitung($el))"
                    @input="$nextTick(() => hitung($el))"
                    @submit="if ($el.querySelector('[data-existing]:disabled') && !confirm('Hapus bobot mata pelajaran yang dinonaktifkan?')) $event.preventDefault();">
                    @csrf
                    @method('PUT')
                    <p class="text-sm">Aktifkan switch untuk menyertakan mata pelajaran. Mapel yang dinonaktifkan
                        akan dihapus dari bobot kelas.</p>
                    @error('bobot')
                        <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
                    @enderror
                    <div class="table-container">
                        <table class="data-table w-full table-fixed">
                            <colgroup>
                                <col class="w-[8%]">
                                <col class="w-[30%]">
                                <col class="w-[62%]">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Bobot (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mapel as $index => $item)
                                    @php
                                        $field = 'bobot.' . $item->id;
                                        $value = old($field, $bobotByMapel->get($item->id)?->bobot_persen);
                                        $dipakai = $value !== null || $errors->has($field);
                                    @endphp
                                    <tr x-data="{ dipakai: @js($dipakai) }">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <label class="flex min-w-0 cursor-pointer items-center gap-3">
                                                <input type="checkbox" role="switch" class="peer sr-only"
                                                    x-model="dipakai" @checked($dipakai)
                                                    aria-controls="bobot-{{ $item->id }}">
                                                <span aria-hidden="true"
                                                    class="relative h-5 w-9 shrink-0 rounded-full bg-slate-500 after:absolute after:start-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:content-[''] peer-checked:bg-blue-700 peer-checked:after:translate-x-4 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-600 peer-focus-visible:ring-offset-2"></span>
                                                <span>{{ $item->nama_mapel }}</span>
                                            </label>
                                            <input type="hidden" name="bobot[{{ $item->id }}]" value=""
                                                :disabled="dipakai" @disabled($dipakai)>
                                        </td>
                                        <td>
                                            <input id="bobot-{{ $item->id }}" name="bobot[{{ $item->id }}]"
                                                type="number" min="0" max="100" step="0.01"
                                                value="{{ $value }}"
                                                aria-label="Bobot {{ $item->nama_mapel }} (persen)"
                                                :disabled="!dipakai" :required="dipakai"
                                                @disabled(!$dipakai)
                                                @if ($bobotByMapel->has($item->id)) data-existing @endif
                                                @error($field) aria-invalid="true" aria-describedby="error-{{ $item->id }}" @enderror
                                                class="w-28 rounded-lg border border-slate-400 px-2 py-1 disabled:bg-slate-100 disabled:text-slate-500">
                                            @error($field)
                                                <p id="error-{{ $item->id }}" class="text-sm text-red-700">
                                                    {{ $message }}</p>
                                            @enderror
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="2">Total bobot</td>
                                    <td>
                                        <output aria-live="polite"
                                            class="flex min-w-0 items-center gap-1 overflow-hidden whitespace-nowrap"
                                            :class="total !== 100 && 'text-amber-800'" :title="total + '%'">
                                            <span class="min-w-0 truncate"
                                                x-text="total">{{ round($kriteriaBobotMenu->sum('bobot_persen'), 2) }}</span>%
                                            <span class="shrink-0"
                                                x-text="total === 100 ? '· Lengkap' : total < 100 ? '· Kurang dari 100%' : '· Melebihi 100%'"></span>
                                        </output>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="flex justify-end ">
                        <button type="submit" class="w-fit primary-button">Simpan Perubahan</button>

                    </div>
                </form>
            @endif
        @else
            <div class="table-container">
                <table class="data-table w-full table-fixed">
                    <colgroup>
                        <col class="w-[8%]">
                        <col class="w-[30%]">
                        <col class="w-[62%]">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Mata Pelajaran</th>
                            <th>Bobot (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kriteriaBobotMenu as $index => $item)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $item->masterMataPelajaran?->nama_mapel ?? 'Mapel Tidak Ditemukan' }}</td>
                                <td>{{ $item->bobot_persen }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center">Belum ada mata pelajaran yang diatur.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($kriteriaBobotMenu->isNotEmpty())
                        <tfoot>
                            <tr>
                                <td colspan="2">Total bobot</td>
                                <td>{{ round($kriteriaBobotMenu->sum('bobot_persen'), 2) }}%</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        @endif
    </div>
</x-app-layout>
