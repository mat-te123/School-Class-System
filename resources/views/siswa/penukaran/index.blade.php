<x-app-layout title="Pengajuan Penukaran Kelas" type="siswa">
    <div x-data="{ showaddmodal: false, paket_list: '', showDeleteModal: false, deleteUrl: '', confirmationData: 'pengajuan' }">
        <div class="flex flex-col border-b border-black px-4 py-6 sm:px-8">
            <h1 class="mainTitle">
                Pengajuan Penukaran Kelas
            </h1>
            <span class="subTitle">Ajukan penukaran kelas kepada guru</span>
        </div>
        <div class="flex flex-col gap-6 px-4 py-6 sm:px-8">
            <a href="{{ route('siswa.dashboard') }} type="button"
                class="primary-button h-fit w-full whitespace-nowrap sm:w-fit">
                Kembali
            </a>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="mainTitle text-lg">
                        Daftar pengajuan penukaran kelas
                    </h2>
                    <p class="subTitle text-sm">
                        Pantau status penukaran yang sudah Anda ajukan.
                    </p>
                </div>

                @if ($pengajuan != null)
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        @if ($pengajuan->status == 'menunggu')
                            <button type="button"
                                class="negative-primary-button bg-red-500 text-white hover:bg-red-600 h-fit w-full whitespace-nowrap sm:w-fit"
                                @click="showDeleteModal= true;
                    deleteUrl = '{{ route('siswa.pertukaran.cancel', $pengajuan->id) }}';
                    ">
                                Hapus pengajuan
                            </button>
                        @endif
                        <button type="button" disable
                            class="primary-button opacity-50 cursor-not-allowed h-fit w-full whitespace-nowrap sm:w-fit">
                            Ajukan penukaran kelas
                        </button>



                    </div>
                @else
                    <button type="button" class="primary-button h-fit w-full whitespace-nowrap sm:w-fit"
                        @click="showaddmodal = true;
                    paket_list = {{ json_encode($paket_list) }};
                    ">
                        Ajukan penukaran kelas
                    </button>
                @endif


            </div>

            <div class="flex flex-col gap-4">
                @if ($pengajuan == null)
                    <div
                        class="flex flex-col items-center justify-center gap-3 rounded-lg border border-gray-300 bg-gray-50 px-6 py-12 text-center">
                        <span class="text-base font-semibold text-gray-700">
                            Belum ada pengajuan penukaran kelas yang diajukan
                        </span>
                        <span class="max-w-md text-sm text-gray-500">
                            Ajukan penukaran ketika periode penukaran sedang dibuka.
                        </span>
                    </div>
                @else
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Kelas Asal</th>
                                    <th>Kelas Tujuan</th>
                                    <th>Alasan</th>
                                    <th>Dokumen Pendukung</th>
                                    <th>Status</th>
                                    @if ($pengajuan->status == 'ditolak')
                                        <th>Alasan Penolakan</th>
                                    @endif
                                    <th>Tanggal Pengajuan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $pengajuan->paketAsal->nama_menu }}</td>
                                    <td>{{ $pengajuan->paketTujuan->nama_menu }}</td>
                                    <td>{{ $pengajuan->alasan }}</td>
                                    <td>
                                        @if ($pengajuan->dokumen_persetujuan_path)
                                            <a href="{{ asset('storage/' . $pengajuan->dokumen_persetujuan_path) }}"
                                                target="_blank" class="text-blue-600 hover:underline">
                                                Lihat Dokumen
                                            </a>
                                        @else
                                            <span class="text-gray-500">Tidak ada dokumen</span>
                                        @endif
                                    </td>
                                    @if ($pengajuan->status == 'menunggu')
                                        <td>
                                            <p class="status-badge status-badge-info">
                                                Menunggu
                                            </p>
                                        </td>
                                    @elseif ($pengajuan->status == 'disetujui')
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
                                    @if ($pengajuan->status == 'ditolak')
                                        <td>{{ $pengajuan->catatan_admin }}</td>
                                    @endif
                                    <td>{{ $pengajuan->created_at->format('d M Y') }}</td>
                                </tr>

                            </tbody>
                        </table>
                    </div>
                @endif
                @if ($pengajuan != null)
                    @if ($pengajuan == 'menunggu')
                        <span class="text-xs text-red-500">
                            Catatan: Siswa hanya dapat mengajukan penukaran kelas satu kali. jika ingin mengajukan
                            kembali,
                            silahkan hapus pengajuan yang sedang diproses atau telah disetujui *.
                        </span>
                    @else
                        <span class="text-xs text-red-500">
                            Catatan: Siswa tidak dapat mengajukan penukaran kelas lagi, setelah pengajuan diproses guru
                            *.
                        </span>
                    @endif
                @endif

            </div>

        </div>
        <x-add-modal-pengajuan-penukaran />
        <x-delete-modal />
        <pre>
            {{ json_encode($pengajuan, JSON_PRETTY_PRINT) }}
        </pre>
    </div>
</x-app-layout>
