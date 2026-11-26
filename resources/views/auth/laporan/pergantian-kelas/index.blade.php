<x-app-layout title="Laporan Pergantian Kelas">
    <div x-data="{ showConfirmationModal: false, confirmLink: '', mainTitle: '', subTitle: '', confirmationData: '', type: '' }" x-effect="console.log(confirmationData)">
        <div class="flex flex-col border-b border-black px-4 py-6 sm:px-8">
            <h1 class="mainTitle">
                Daftar Pengajuan Pergantian Kelas
            </h1>
            <span class="subTitle">Daftar semua pengajuan pergantian kelas yang telah diajukan</span>
        </div>
        <div class="flex flex-col gap-6 px-4 py-6 sm:px-8">
            <a class="primary-button h-fit w-full whitespace-nowrap sm:w-fit" href="{{ route('admin-siswa.index') }}">
                Kembali
            </a>
            <form method="GET" action="{{ route('admin.pertukaran.index') }}"
                class="flex flex-row bg-gray-100 p-3 w-full border border-gray-400 rounded-lg gap-3 items-center">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama, NISN, atau NIS..." oninput="this.value = this.value.toLowerCase()"
                    class="w-full py-2 px-4 bg-[#F9FAFB] border border-gray-400 rounded-lg text-sm">

                @foreach (['semua' => '', 'menunggu' => 'menunggu', 'disetujui' => 'disetujui', 'ditolak' => 'ditolak'] as $status => $label)
                    <a href="{{ route('admin.pertukaran.index', ['status' => $label]) }}"
                        class="w-fit whitespace-nowrap text-sm rounded-lg px-4 py-2 {{ request('status') == $label ? 'bg-blue-600 text-white border border-blue-200' : 'bg-white text-gray-700  border border-gray-300 hover:border hover:border-[#3b82f6]' }}">
                        {{ ucfirst($status) }}
                    </a>
                @endforeach
            </form>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama Siswa</th>
                            <th>Alasan</th>
                            <th>Kelas Asal</th>
                            <th>Kelas Tujuan</th>
                            <th>Dokumen Pendukung</th>
                            <th>Status</th>
                            <th>Aksi</th>

                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pengajuan as $index => $item)
                            <tr class="group hover:bg-gray-100">
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <div class="flex flex-row items-center gap-2 lowercase justify-between text-md">
                                        {{ $item->siswa->nama_lengkap }}
                                        <a href="{{ route('siswa.show', $item->siswa->id) }}"
                                            class="opacity-0 group-hover:opacity-100 bg-white hover:bg-gray-300 rounded-lg p-1 transition-all duration-100 ease-in-out active:scale-110 active:shadow-lg">
                                            <img src="{{ asset('Icon/Arrow_down.svg') }}" alt="Arrow icon"
                                                class="w-4 h-4">
                                        </a>
                                    </div>
                                </td>
                                <td>{{ $item->alasan }}</td>
                                <td>{{ $item->paketAsal->nama_menu }}</td>
                                <td>{{ $item->paketTujuan->nama_menu }}</td>
                                <td>
                                    @if ($item->dokumen_persetujuan_path)
                                        <a href="{{ asset('storage/' . $item->dokumen_persetujuan_path) }}"
                                            target="_blank" class="text-blue-600 hover:underline">
                                            Lihat Dokumen
                                        </a>
                                    @else
                                        <span class="text-gray-500">Tidak ada dokumen</span>
                                    @endif
                                </td>
                                @if ($item->status == 'menunggu')
                                    <td>
                                        <p class="status-badge status-badge-info">
                                            Menunggu
                                        </p>
                                    </td>
                                @elseif ($item->status == 'disetujui')
                                    <td>
                                        <p class="status-badge status-badge-success">
                                            Disetujui
                                        </p>
                                    </td>
                                @else
                                    <td>
                                        <p class="status-badge status-badge-danger">
                                            Ditolak
                                        </p>
                                    </td>
                                @endif
                                @if ($item->status == 'menunggu')
                                    <td>
                                        <button type="button"
                                            @click="
                                            type='approval';
                                            showConfirmationModal = true;
                                            confirmLink = '{{ route('admin.pertukaran.approve', $item->id) }}';
                                            mainTitle = 'Konfirmasi Persetujuan';
                                            confirmationData = '{{ $item->siswa->nama_lengkap }}';
                                            subTitle = 'Apakah Anda yakin ingin menyetujui pengajuan pergantian kelas untuk ';
                                        "
                                            class="mr-2 text-xs text-white bg-blue-600 px-2 py-1 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                                            Setujui
                                        </button>
                                        <button type="button"
                                            @click="
                                            type='reject';
                                            showConfirmationModal = true;
                                            confirmLink = '{{ route('admin.pertukaran.reject', $item->id) }}';
                                            confirmationData = '{{ $item->siswa->nama_lengkap }}';
                                            mainTitle = 'Konfirmasi Penolakan';
                                            subTitle = 'Apakah Anda yakin ingin menolak pengajuan pergantian kelas untuk ';
                                        "
                                            class="text-xs text-white bg-red-600 px-2 py-1 rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                            Tolak
                                        </button>

                                    </td>
                                @else
                                    <td>
                                        <p class="text-sm text-gray-500">
                                            Sudah diproses
                                        </p>
                                    </td>
                                @endif


                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center w-full py-8">
                                    <div class="flex flex-col items-center justify-center py-12 px-2  ">
                                        <h3 class="mainTitle text-lg">
                                            Data tidak ditemukan

                                        </h3>
                                        @if (request()->anyFilled(['search', 'status', 'tanggal']))
                                            <p class="subTitle text-sm">
                                                Tidak ada data untuk filter ini
                                            </p>
                                        @elseif ($pengajuan->isEmpty())
                                            <p class="subTitle text-sm">
                                                Tidak ada data pengajuan yang dibuat
                                            </p>
                                        @else
                                            <p class="subTitle text-sm">
                                                Data kosong
                                            </p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
        <div x-show="showConfirmationModal" x-cloak
            class="flex flex-col fixed top-0 left-0 w-full h-full bg-black/50 z-50 justify-center items-center"
            style="display: none;">
            <form method="POST" :action="confirmLink" class="flex flex-col bg-white rounded-lg p-6 gap-4 w-[36rem]"
                @click.away="showConfirmModal = false">
                @csrf
                @method('PUT')
                <div class="flex flex-col gap-2">
                    <h2 class="mainTitle" x-text="mainTitle">
                    </h2>

                    <div class="subTitle w-fit">
                        <span x-text="subTitle"></span>
                        <span class="text-sm font-semibold text-gray-900" x-text="confirmationData"></span>?
                    </div>


                </div>
                <template x-if="type === 'reject'">
                    <div class="flex flex-col gap-2">
                        <label for="catatan_admin" class="block text-sm font-medium text-gray-700 mb-1">
                            Catatan Penolakan (Wajib)
                        </label>
                        <input type="text-area" name="catatan_admin" placeholder="Masukkan catatan penolakan (Wajib)"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </template>

                <div class="flex flex-row justify-end gap-2">
                    <button type="button" @click="showConfirmationModal = false"
                        class="text-sm px-4 py-2 text-white bg-red-500 rounded-md hover:bg-red-600">
                        Batal
                    </button>
                    <button type="submit"
                        class="text-sm px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Ya
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
