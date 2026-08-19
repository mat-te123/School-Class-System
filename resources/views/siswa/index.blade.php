<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manajemen Siswa - School Class System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans leading-normal tracking-normal" x-data="siswaTable()">

    <!-- Navigation Header -->
    <nav class="bg-blue-600 shadow-lg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <span class="font-bold text-xl tracking-wider">🎓 Manajemen Siswa</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm bg-blue-700 px-3 py-1 rounded-full">Administrator</span>
                    <a href="/" class="text-xs bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded font-semibold transition">Kembali</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Container Utama -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">

        <!-- Header + Tombol Tambah -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Daftar Siswa</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Total <span class="font-semibold text-blue-600" x-text="meta.total || 0"></span> siswa terdaftar.
                </p>
            </div>
            <button @click="openAddModal()"
                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                ➕ Tambah Siswa
            </button>
        </div>

        <!-- Alert Pesan -->
        <template x-if="formMessage">
            <div class="p-4 rounded-xl text-sm font-medium border"
                 :class="formError ? 'bg-red-50 text-red-700 border-red-200' : 'bg-green-50 text-green-700 border-green-200'"
                 x-text="formMessage"></div>
        </template>

        <!-- Toolbar: Search & Per Page -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex flex-col md:flex-row md:items-center gap-4">
            <div class="flex-1">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">🔍 Cari Siswa</label>
                <input type="text"
                       x-model="search"
                       @input.debounce.500ms="page = 1; fetchSiswa()"
                       placeholder="Cari NISN, NIS, atau Nama Lengkap..."
                       class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
            </div>
            <div class="md:w-48">
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Tampilkan</label>
                <select x-model="perPage" @change="page = 1; fetchSiswa()"
                        class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm bg-white focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                    <option value="5">5 / halaman</option>
                    <option value="10">10 / halaman</option>
                    <option value="25">25 / halaman</option>
                    <option value="50">50 / halaman</option>
                </select>
            </div>
            <div class="md:self-end">
                <button @click="fetchSiswa()"
                        class="w-full md:w-auto bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-4 py-2.5 rounded-lg transition">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Tabel Data -->
        <div class="bg-white shadow rounded-xl overflow-hidden">
            <div x-show="loading" class="px-6 py-4 text-sm text-blue-600 font-semibold">⏳ Memuat data...</div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider w-12">No</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">NISN</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">NIS</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Nama Lengkap</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Kelas Asal</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Jenis Kelamin</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Angkatan</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template x-for="(item, index) in siswa" :key="item.id">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500" x-text="((meta.current_page || 1) - 1) * (meta.per_page || 10) + index + 1"></td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-700" x-text="item.nisn"></td>
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-700" x-text="item.nis"></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900" x-text="item.nama_lengkap"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500" x-text="item.kelas_asal_relation?.nama_kelas || item.kelas_asal || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                          :class="item.jenis_kelamin === 'L' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800'"
                                          x-text="item.jenis_kelamin === 'L' ? 'Laki-laki' : item.jenis_kelamin === 'P' ? 'Perempuan' : '-'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-gray-500" x-text="item.angkatan || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full"
                                          :class="item.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="item.is_active ? 'Aktif' : 'Nonaktif'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center space-x-2">
                                    <button @click="openEditModal(item)"
                                            class="inline-block bg-amber-50 hover:bg-amber-100 text-amber-600 px-3 py-1.5 rounded-lg text-xs font-semibold transition">✏️ Edit</button>
                                    <button @click="openDeleteModal(item)"
                                            class="inline-block bg-red-50 hover:bg-red-100 text-red-600 px-3 py-1.5 rounded-lg text-xs font-semibold transition">🗑️ Hapus</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!loading && siswa.length === 0">
                            <td colspan="9" class="px-6 py-10 text-center text-gray-500">
                                Belum ada data siswa yang cocok dengan pencarian.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <div class="px-6 py-4 border-t border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <div class="text-xs text-gray-500">
                    Menampilkan <span class="font-semibold text-gray-700" x-text="meta.from || 0"></span> –
                    <span class="font-semibold text-gray-700" x-text="meta.to || 0"></span> dari
                    <span class="font-semibold text-gray-700" x-text="meta.total || 0"></span> data
                </div>
                <div class="flex items-center space-x-1">
                    <button @click="goToPage((meta.current_page || 1) - 1)"
                            :disabled="!meta.prev_page_url"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="meta.prev_page_url ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-gray-50 text-gray-400'">
                        ← Prev
                    </button>
                    <template x-for="p in pages" :key="p">
                        <button @click="goToPage(p)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold transition"
                                :class="p === (meta.current_page || 1) ? 'bg-blue-600 text-white shadow' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                                x-text="p"></button>
                    </template>
                    <button @click="goToPage((meta.current_page || 1) + 1)"
                            :disabled="!meta.next_page_url"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition disabled:opacity-40 disabled:cursor-not-allowed"
                            :class="meta.next_page_url ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-gray-50 text-gray-400'">
                        Next →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT -->
    <div x-show="showFormModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-5 shadow-2xl max-h-[90vh] overflow-y-auto" @click.away="closeFormModal()">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-xl font-bold text-gray-900" x-text="editId ? '✏️ Edit Siswa' : '➕ Tambah Siswa'"></h3>
                <button @click="closeFormModal()" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">NISN *</label>
                        <input type="text" x-model="form.nisn" required maxlength="10"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">NIS *</label>
                        <input type="text" x-model="form.nis" required maxlength="10"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap *</label>
                    <input type="text" x-model="form.nama_lengkap" required maxlength="150"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kelas Asal</label>
                        <select x-model="form.kelas_asal_id"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="">-- Pilih Kelas --</option>
                            <template x-for="kelas in kelasAsalOptions" :key="kelas.id">
                                <option :value="kelas.id" x-text="kelas.nama_kelas"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Kelas Asal (Teks)</label>
                        <input type="text" x-model="form.kelas_asal" maxlength="50"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Kelamin</label>
                        <select x-model="form.jenis_kelamin"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option value="">-- Pilih --</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Tanggal Lahir</label>
                        <input type="date" x-model="form.tanggal_lahir"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Angkatan</label>
                        <input type="text" x-model="form.angkatan" maxlength="4" placeholder="2026"
                               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring focus:ring-blue-200">
                    </div>
                    <div x-show="editId">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Status Aktif</label>
                        <select x-model="form.is_active"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm bg-white focus:border-blue-500 focus:ring focus:ring-blue-200">
                            <option :value="true">Aktif</option>
                            <option :value="false">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end space-x-3">
                    <button type="button" @click="closeFormModal()"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg text-sm transition">Batal</button>
                    <button type="submit" :disabled="isSubmitting"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isSubmitting" x-text="editId ? 'Simpan Perubahan' : 'Tambah Siswa'"></span>
                        <span x-show="isSubmitting">⏳ Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 space-y-5 shadow-2xl">
            <div class="text-center space-y-2">
                <div class="text-4xl">🗑️</div>
                <h3 class="text-lg font-bold text-gray-900">Hapus Data Siswa?</h3>
                <p class="text-sm text-gray-500">
                    Anda yakin ingin menghapus <span class="font-semibold text-gray-700" x-text="deleteTarget?.nama_lengkap"></span>?
                    Tindakan ini tidak dapat dibatalkan.
                </p>
            </div>
            <div class="flex justify-end space-x-3">
                <button @click="showDeleteModal = false"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg text-sm transition">Batal</button>
                <button @click="confirmDelete" :disabled="isSubmitting"
                        class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2 rounded-lg text-sm transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!isSubmitting">Ya, Hapus</span>
                    <span x-show="isSubmitting">⏳ Menghapus...</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        function siswaTable() {
            return {
                siswa: [],
                meta: {},
                search: '',
                perPage: 10,
                page: 1,
                loading: false,
                kelasAsalOptions: @json($kelasAsal ?? []),

                // Form state
                showFormModal: false,
                showDeleteModal: false,
                editId: null,
                deleteTarget: null,
                isSubmitting: false,
                formMessage: '',
                formError: false,
                form: {
                    nisn: '',
                    nis: '',
                    nama_lengkap: '',
                    kelas_asal_id: '',
                    kelas_asal: '',
                    jenis_kelamin: '',
                    tanggal_lahir: '',
                    angkatan: '',
                    is_active: true,
                },

                async init() {
                    await this.fetchSiswa();
                },

                get pages() {
                    if (!this.meta || !this.meta.last_page) return [];
                    const total = this.meta.last_page;
                    const current = this.meta.current_page;
                    const start = Math.max(1, current - 2);
                    const end = Math.min(total, current + 2);
                    const pages = [];
                    for (let i = start; i <= end; i++) pages.push(i);
                    return pages;
                },

                async fetchSiswa() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            search: this.search,
                            per_page: this.perPage,
                            page: this.page,
                        });
                        const res = await fetch('/siswa?' + params.toString(), {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.siswa = json.data.data;
                            this.meta = json.data;
                        }
                    } catch (e) {
                        console.error('Error fetching siswa:', e);
                    } finally {
                        this.loading = false;
                    }
                },

                goToPage(p) {
                    if (p < 1 || p > (this.meta.last_page || 1)) return;
                    this.page = p;
                    this.fetchSiswa();
                },

                resetForm() {
                    this.editId = null;
                    this.form = {
                        nisn: '',
                        nis: '',
                        nama_lengkap: '',
                        kelas_asal_id: '',
                        kelas_asal: '',
                        jenis_kelamin: '',
                        tanggal_lahir: '',
                        angkatan: '',
                        is_active: true,
                    };
                },

                openAddModal() {
                    this.resetForm();
                    this.formMessage = '';
                    this.showFormModal = true;
                },

                openEditModal(item) {
                    this.editId = item.id;
                    this.form = {
                        nisn: item.nisn,
                        nis: item.nis,
                        nama_lengkap: item.nama_lengkap,
                        kelas_asal_id: item.kelas_asal_id || '',
                        kelas_asal: item.kelas_asal || '',
                        jenis_kelamin: item.jenis_kelamin || '',
                        tanggal_lahir: item.tanggal_lahir || '',
                        angkatan: item.angkatan || '',
                        is_active: item.is_active,
                    };
                    this.formMessage = '';
                    this.showFormModal = true;
                },

                closeFormModal() {
                    this.showFormModal = false;
                    this.formMessage = '';
                },

                openDeleteModal(item) {
                    this.deleteTarget = item;
                    this.formMessage = '';
                    this.showDeleteModal = true;
                },

                async submitForm() {
                    this.isSubmitting = true;
                    this.formMessage = '';
                    this.formError = false;
                    try {
                        const url = this.editId ? '/siswa/' + this.editId : '/siswa';
                        const method = this.editId ? 'PUT' : 'POST';
                        const res = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify(this.form),
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.formMessage = data.message || 'Data siswa berhasil disimpan.';
                            this.formError = false;
                            this.closeFormModal();
                            await this.fetchSiswa();
                        } else {
                            this.formError = true;
                            const first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Terjadi kesalahan.');
                            this.formMessage = first;
                        }
                    } catch (e) {
                        console.error('Error submitting:', e);
                        this.formError = true;
                        this.formMessage = 'Terjadi kesalahan jaringan saat menyimpan data.';
                    } finally {
                        this.isSubmitting = false;
                    }
                },

                async confirmDelete() {
                    if (!this.deleteTarget) return;
                    this.isSubmitting = true;
                    this.formMessage = '';
                    try {
                        const res = await fetch('/siswa/' + this.deleteTarget.id, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                        });
                        const data = await res.json();
                        if (res.ok) {
                            this.formMessage = data.message || 'Data siswa berhasil dihapus.';
                            this.formError = false;
                            this.showDeleteModal = false;
                            this.deleteTarget = null;
                            await this.fetchSiswa();
                        } else {
                            this.formError = true;
                            this.formMessage = data.message || 'Terjadi kesalahan saat menghapus data.';
                        }
                    } catch (e) {
                        console.error('Error deleting:', e);
                        this.formError = true;
                        this.formMessage = 'Terjadi kesalahan jaringan saat menghapus data.';
                    } finally {
                        this.isSubmitting = false;
                    }
                },
            }
        }
    </script>
</body>
</html>
