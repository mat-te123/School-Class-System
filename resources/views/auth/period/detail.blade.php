<x-app-layout title="Detail Periode">
    @php
        $exchangeOpen =
            $periode->tanggal_mulai_pertukaran &&
            now()->between(
                $periode->tanggal_mulai_pertukaran,
                $periode->tanggal_selesai_pertukaran ?? $periode->tanggal_mulai_pertukaran,
            );
        $exchangeConfigured = $periode->tanggal_mulai_pertukaran && $periode->tanggal_selesai_pertukaran;
    @endphp

    <div class="flex min-h-0 flex-1 flex-col">
        <div class="flex flex-col border border-b-black px-8 py-6">
            <h1 class="mainTitle">
                Periode > <span class="text-2xl lowercase text-gray-500">{{ $periode->nama_periode }}</span>
            </h1>
            <span class="subTitle">
                Kelola informasi dan akses periode penjurusan
            </span>
        </div>

        <div class="relative flex flex-col gap-4 px-8 pt-6">
            <a href="{{ route('admin-periode-penjurusan.index') }}" class="primary-button w-fit">
                Kembali
            </a>
            <div class="flex flex-col gap-4 lg:flex-row">
                <section class="flex w-full flex-col gap-4 rounded-lg border border-[#D1D5DB] bg-[#F9FAFB] p-4 lg:w-2/3">
                    <div class="flex flex-col gap-2 border-b border-gray-300 pb-2">
                        <h2 class="text-2xl font-semibold leading-6">{{ $periode->nama_periode }}</h2>
                        <span class="text-sm text-gray-500">
                            T.A {{ $periode->tahun_ajaran }} · Gelombang {{ $periode->gelombang }}
                        </span>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">Pendaftaran</span>
                            <span class="text-base font-semibold">{{ $periode->tanggal_buka?->format('d M Y') }} -
                                {{ $periode->tanggal_tutup?->format('d M Y') }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">Pengumuman</span>
                            <span class="text-base font-semibold">
                                {{ $periode->isPengumumanDibuka() ? 'Sudah dibuka' : 'Belum dibuka' }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">Penukaran</span>
                            <span class="text-base font-semibold">
                                {{ $exchangeOpen ? 'Sedang dibuka' : ($exchangeConfigured ? 'Sudah diatur' : 'Belum diatur') }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-sm text-gray-500">Pilihan siswa</span>
                            <span class="text-base font-semibold">{{ $periode->max_pilihan_siswa }}</span>
                        </div>
                    </div>
                </section>
                <section class="flex w-full flex-col justify-between gap-3 rounded-lg border border-[#D1D5DB] bg-white p-4 lg:w-1/3">
                    <div>
                        <h2 class="text-lg font-semibold">Akses data periode</h2>
                        <p class="text-sm text-gray-500">Buka data tanpa mengubah status publikasi periode.</p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <a href="{{ route('admin.hasil-penjurusan.index', ['periode_id' => $periode->id]) }}"
                            class="primary-button w-full whitespace-nowrap sm:w-fit">
                            Cek hasil seleksi
                        </a>
                        <a href="{{ route('admin.pemantauan-minat.index', ['periode_id' => $periode->id]) }}"
                            class="negative-primary-button w-full whitespace-nowrap sm:w-fit">
                            Cek persebaran kelas
                        </a>
                    </div>
                </section>

            </div>



            <div class="flex flex-col border-b border-gray-300 pb-2">
                <h2 class="mainTitle text-lg">
                    Jadwal Penukaran
                </h2>
                <p class="subTitle text-sm">
                    Jadwal penukaran dapat diatur setelah hasil seleksi dipublikasikan. Siswa hanya dapat mengajukan
                    penukaran selama jadwal ini berlangsung.
                </p>
            </div>
            @if ($periode->is_active)
                <section class="max-w-2xl rounded-lg border border-[#D1D5DB] bg-white p-4">
                    <h2 class="text-lg font-semibold">Jadwal penukaran</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Siswa dapat mengajukan penukaran hanya selama jadwal ini berlangsung.
                    </p>



                    <form action="{{ route('periode-penjurusan.update', ['id' => $periode->id]) }}" method="POST"
                        class="mt-5 flex flex-col gap-4">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="flex flex-col gap-1">
                                <label for="tanggal_mulai_pertukaran" class="text-sm font-semibold">Mulai</label>
                                <input id="tanggal_mulai_pertukaran" name="tanggal_mulai_pertukaran"
                                    type="datetime-local"
                                    value="{{ $periode->tanggal_mulai_pertukaran?->format('Y-m-d\TH:i') }}" required
                                    class="rounded-lg border border-gray-300 px-3 py-2" @disabled(!$periode->isPengumumanDibuka())>
                            </div>
                            <div class="flex flex-col gap-1">
                                <label for="tanggal_selesai_pertukaran" class="text-sm font-semibold">Selesai</label>
                                <input id="tanggal_selesai_pertukaran" name="tanggal_selesai_pertukaran"
                                    type="datetime-local"
                                    value="{{ $periode->tanggal_selesai_pertukaran?->format('Y-m-d\TH:i') }}" required
                                    class="rounded-lg border border-gray-300 px-3 py-2" @disabled(!$periode->isPengumumanDibuka())>
                            </div>
                        </div>
                        @if (!$periode->isPengumumanDibuka())
                            <p class="text-sm text-amber-700">Jadwal tersedia setelah hasil seleksi dipublikasikan.</p>
                        @endif
                        <div class="w-full justify-end flex">
                            <button type="submit" class="primary-button w-fit" @disabled(!$periode->isPengumumanDibuka())>
                                Simpan jadwal
                            </button>
                        </div>
                    </form>
                </section>
            @else
                <div class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900"
                    role="status">
                    Periode ini tidak aktif. Jadwal penukaran hanya dapat diatur pada periode yang aktif.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
