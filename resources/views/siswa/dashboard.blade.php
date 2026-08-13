<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Siswa - School Class System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50 font-sans leading-normal tracking-normal" x-data="siswaDashboard()">

    <!-- Navigation Header -->
    <nav class="bg-blue-600 shadow-lg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <span class="font-bold text-xl tracking-wider">🎓 Portal Siswa</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm bg-blue-700 px-3 py-1 rounded-full" x-text="profile.nama_lengkap || 'Siswa'"></span>
                    <form action="{{ route('siswa.logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-xs bg-red-500 hover:bg-red-600 px-3 py-1.5 rounded font-semibold transition">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Container Utama -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- Profile Banner -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-xl text-white p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center">
            <div class="space-y-2">
                <h1 class="text-2xl md:text-3xl font-bold" x-text="'Selamat Datang, ' + (profile.nama_lengkap || '')"></h1>
                <p class="text-blue-100 text-sm md:text-base">
                    NISN: <span class="font-semibold" x-text="profile.nisn"></span> | NIS: <span class="font-semibold" x-text="profile.nis"></span> | Angkatan: <span class="font-semibold" x-text="profile.angkatan"></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0 bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl text-right">
                <div class="text-xs uppercase tracking-wider text-blue-200">Status Akun</div>
                <div class="text-sm font-bold text-green-300">● AKTIF</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'nilai'" 
                        :class="activeTab === 'nilai' ? 'border-blue-500 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 text-sm sm:text-base transition">
                    📚 Nilai Saya (FR-49)
                </button>
                <button @click="activeTab = 'paket'" 
                        :class="activeTab === 'paket' ? 'border-blue-500 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 text-sm sm:text-base transition">
                    📦 Paket Menu Aktif (FR-50 & FR-51)
                </button>
                <button @click="activeTab = 'pilih'" 
                        :class="activeTab === 'pilih' ? 'border-blue-500 text-blue-600 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="py-4 px-1 border-b-2 text-sm sm:text-base transition">
                    📝 Pilih Paket Prioritas (FR-52)
                </button>
            </nav>
        </div>

        <!-- TAB 1: Nilai Siswa (FR-49) -->
        <div x-show="activeTab === 'nilai'" x-cloak class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-800">Daftar Nilai Mata Pelajaran</h2>
                <button @click="fetchNilai()" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded transition">
                    🔄 Refresh Data
                </button>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">No</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Mata Pelajaran</th>
                            <th class="px-6 py-3 text-left font-semibold text-gray-600 uppercase tracking-wider">Kode</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Nilai Angka</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-600 uppercase tracking-wider">Predikat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template x-for="(item, index) in dataNilai" :key="item.id || index">
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500" x-text="index + 1"></td>
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900" x-text="item.mata_pelajaran?.nama_mapel || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500" x-text="item.mata_pelajaran?.kode_mapel || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center font-bold text-blue-600" x-text="item.nilai_angka || item.nilai"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-2.5 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800" x-text="item.predikat || 'B'"></span>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="dataNilai.length === 0">
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">Belum ada data nilai yang tersedia.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB 2: Paket Menu Aktif (FR-50 & FR-51) -->
        <div x-show="activeTab === 'paket'" x-cloak class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Daftar Paket Menu Pilihan (Periode Berjalan)</h2>
                    <p class="text-xs text-gray-500">Pilih paket menu sesuai kriteria dan minat Anda.</p>
                </div>
                <button @click="fetchPaket()" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-1.5 rounded transition">
                    🔄 Refresh Paket
                </button>
            </div>

            <!-- Periode Banner -->
            <template x-if="metaPeriode.length > 0">
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-xl">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-amber-800" x-text="'Periode Pendaftaran: ' + metaPeriode[0].nama_periode"></h3>
                            <p class="text-xs text-amber-700 mt-1" x-text="'Tahun Ajaran: ' + metaPeriode[0].tahun_ajaran + ' | Gelombang: ' + metaPeriode[0].gelombang"></p>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Cards Grid (FR-50) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="paket in listPaket" :key="paket.id">
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 hover:shadow-xl transition duration-300 flex flex-col justify-between p-6">
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-xs font-bold uppercase tracking-wider px-2.5 py-1 rounded-md"
                                      :class="paket.rumpun === 'eksakta' ? 'bg-indigo-100 text-indigo-700' : 'bg-orange-100 text-orange-700'"
                                      x-text="paket.rumpun"></span>
                                <span class="text-xs text-gray-400" x-text="'Sisa Kuota: ' + paket.kuota_tersisa"></span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900" x-text="paket.nama_menu"></h3>
                            
                            <!-- Mini Kriteria Preview -->
                            <div class="text-xs text-gray-600 space-y-1 bg-gray-50 p-3 rounded-lg">
                                <div class="font-semibold text-gray-500 mb-1">Mata Pelajaran Utama & Bobot:</div>
                                <template x-for="kb in (paket.kriteria_bobot || [])" :key="kb.id">
                                    <div class="flex justify-between">
                                        <span x-text="kb.nama_mapel"></span>
                                        <span class="font-bold text-gray-700" x-text="kb.bobot_persen + '%'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Action Button (FR-51 Modal Trigger) -->
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <button @click="viewDetail(paket.id)" 
                                    class="w-full bg-blue-50 hover:bg-blue-100 text-blue-600 font-semibold py-2 px-4 rounded-lg text-sm transition text-center">
                                🔍 Lihat Detail Paket (FR-51)
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- MODAL DETAIL PAKET (FR-51) -->
        <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl max-w-lg w-full p-6 space-y-6 shadow-2xl animate-fade-in" @click.away="showModal = false">
                <div class="flex justify-between items-center border-b pb-3">
                    <h3 class="text-xl font-bold text-gray-900" x-text="detailPaket.nama_menu"></h3>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="grid grid-cols-2 gap-4 bg-blue-50 p-4 rounded-xl">
                        <div>
                            <span class="text-xs text-blue-500 uppercase font-semibold">Rumpun</span>
                            <div class="font-bold text-blue-900 uppercase" x-text="detailPaket.rumpun"></div>
                        </div>
                        <div>
                            <span class="text-xs text-blue-500 uppercase font-semibold">Kapasitas Kuota</span>
                            <div class="font-bold text-blue-900" x-text="detailPaket.kuota_terisi + ' / ' + detailPaket.kuota_kapasitas"></div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-gray-800 mb-2">Kriteria Bobot Penilaian:</h4>
                        <div class="divide-y divide-gray-100 bg-gray-50 rounded-xl overflow-hidden">
                            <template x-for="kb in (detailPaket.kriteria_bobot || [])" :key="kb.id">
                                <div class="p-3 flex justify-between items-center">
                                    <div>
                                        <div class="font-semibold text-gray-900" x-text="kb.nama_mapel"></div>
                                        <div class="text-xs text-gray-400" x-text="'Kode: ' + kb.kode_mapel + ' | Kelompok: ' + kb.kelompok_mapel"></div>
                                    </div>
                                    <div class="bg-blue-600 text-white px-2.5 py-1 rounded-full text-xs font-bold" x-text="kb.bobot_persen + '%'"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end">
                    <button @click="showModal = false" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg text-sm transition">
                        Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 3: Pendaftaran Pilihan Paket (FR-52) -->
        <div x-show="activeTab === 'pilih'" x-cloak class="space-y-6">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Pemilihan Paket Prioritas</h2>
                    <p class="text-xs text-gray-500">Pilih 3 paket menu prioritas Anda. Pilihan ini bersifat final pada periode ini.</p>
                </div>
            </div>

            <!-- Jika tidak ada periode pendaftaran aktif -->
            <template x-if="metaPeriode.length === 0">
                <div class="bg-red-50 border border-red-200 text-red-700 px-6 py-8 rounded-xl text-center shadow-sm">
                    <div class="text-4xl mb-3">⛔</div>
                    <h3 class="text-lg font-bold">Pendaftaran Ditutup</h3>
                    <p class="text-sm mt-1">Saat ini tidak ada periode pendaftaran paket (penjurusan) yang sedang aktif atau dibuka.</p>
                </div>
            </template>

            <!-- Jika ada periode aktif -->
            <template x-if="metaPeriode.length > 0">
                <div>
                    <!-- State 1: Sudah Mengisi -->
                    <template x-if="dataPendaftaran && dataPendaftaran.id">
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center space-y-4">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full mb-2">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-800">Pilihan Telah Tersimpan!</h3>
                            <p class="text-gray-500">Anda sudah memilih paket pada tanggal <span class="font-semibold text-gray-700" x-text="new Date(dataPendaftaran.tanggal_submit).toLocaleString('id-ID')"></span></p>
                            
                            <div class="mt-6 text-left max-w-lg mx-auto space-y-3 bg-gray-50 p-6 rounded-xl border border-gray-100">
                                <template x-for="(detail, index) in dataPendaftaran.detail_pendaftaran" :key="detail.id">
                                    <div class="flex items-center space-x-4 p-3 bg-white rounded-lg shadow-sm border border-gray-50">
                                        <div class="flex-shrink-0 bg-blue-100 text-blue-600 w-8 h-8 flex items-center justify-center rounded-full font-bold" x-text="detail.urutan_pilihan"></div>
                                        <div>
                                            <div class="font-bold text-gray-800" x-text="detail.paket_menu_pilihan.nama_menu"></div>
                                            <div class="text-xs text-gray-500" x-text="'Rumpun: ' + detail.paket_menu_pilihan.rumpun.toUpperCase()"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- State 2: Belum Mengisi (Formulir Pendaftaran) -->
                    <template x-if="!dataPendaftaran || !dataPendaftaran.id">
                        <div class="bg-white rounded-xl shadow-md border border-gray-100 p-6 md:p-8">
                            <div x-show="formMessage" class="mb-6 p-4 rounded-lg text-sm font-medium" 
                                 :class="formError ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-green-50 text-green-700 border border-green-200'"
                                 x-text="formMessage"></div>

                            <form @submit.prevent="submitPilihan" class="space-y-6 max-w-2xl mx-auto">
                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-sm text-blue-800 mb-6 flex items-start space-x-3">
                                    <span class="text-xl">ℹ️</span>
                                    <div>
                                        <strong>Penting:</strong> Pastikan pilihan paket menu Anda sudah dipertimbangkan dengan matang. Prioritas 1 adalah pilihan utama Anda. Pilihan tidak boleh ada yang sama (duplikat).
                                    </div>
                                </div>

                                <!-- Input Prioritas 1 -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">1️⃣ Prioritas Pertama (Utama)</label>
                                    <select x-model="pilihan1" required class="w-full rounded-lg border-gray-300 border px-4 py-3 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                        <option value="">-- Pilih Paket Menu --</option>
                                        <template x-for="paket in listPaket" :key="paket.id">
                                            <option :value="paket.id" x-text="paket.nama_menu + ' (' + paket.rumpun.toUpperCase() + ')'" :disabled="pilihan2 === paket.id || pilihan3 === paket.id"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Input Prioritas 2 -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">2️⃣ Prioritas Kedua</label>
                                    <select x-model="pilihan2" required class="w-full rounded-lg border-gray-300 border px-4 py-3 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                        <option value="">-- Pilih Paket Menu --</option>
                                        <template x-for="paket in listPaket" :key="paket.id">
                                            <option :value="paket.id" x-text="paket.nama_menu + ' (' + paket.rumpun.toUpperCase() + ')'" :disabled="pilihan1 === paket.id || pilihan3 === paket.id"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Input Prioritas 3 -->
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">3️⃣ Prioritas Ketiga</label>
                                    <select x-model="pilihan3" required class="w-full rounded-lg border-gray-300 border px-4 py-3 bg-white focus:border-blue-500 focus:ring focus:ring-blue-200 transition">
                                        <option value="">-- Pilih Paket Menu --</option>
                                        <template x-for="paket in listPaket" :key="paket.id">
                                            <option :value="paket.id" x-text="paket.nama_menu + ' (' + paket.rumpun.toUpperCase() + ')'" :disabled="pilihan1 === paket.id || pilihan2 === paket.id"></option>
                                        </template>
                                    </select>
                                </div>

                                <div class="pt-6 border-t border-gray-100 flex justify-end">
                                    <button type="submit" :disabled="isSubmitting" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg transition-transform transform hover:-translate-y-1 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <span x-show="!isSubmitting">Simpan Pilihan Final</span>
                                        <span x-show="isSubmitting">⏳ Menyimpan...</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>
            </template>
        </div>

    </div>

    <script>
        function siswaDashboard() {
            return {
                activeTab: 'nilai',
                profile: {},
                dataNilai: [],
                listPaket: [],
                metaPeriode: [],
                showModal: false,
                detailPaket: {},
                dataPendaftaran: null,
                pilihan1: '',
                pilihan2: '',
                pilihan3: '',
                isSubmitting: false,
                formMessage: '',
                formError: false,

                async init() {
                    await this.fetchProfile();
                    await this.fetchNilai();
                    await this.fetchPaket();
                    await this.fetchPendaftaran();
                },

                async fetchProfile() {
                    try {
                        const res = await fetch('/siswa/profile');
                        const data = await res.json();
                        if (data.success) {
                            this.profile = data.data.siswa;
                        }
                    } catch (e) {
                        console.error('Error fetching profile:', e);
                    }
                },

                async fetchNilai() {
                    try {
                        const res = await fetch('/siswa/nilai');
                        const data = await res.json();
                        if (data.success) {
                            this.dataNilai = data.data.data || data.data;
                        }
                    } catch (e) {
                        console.error('Error fetching nilai:', e);
                    }
                },

                async fetchPaket() {
                    try {
                        const res = await fetch('/siswa/paket-menu-aktif');
                        const data = await res.json();
                        if (data.success) {
                            this.listPaket = data.data;
                            this.metaPeriode = data.meta.active_periods || [];
                        }
                    } catch (e) {
                        console.error('Error fetching paket:', e);
                    }
                },

                async fetchPendaftaran() {
                    try {
                        const res = await fetch('/siswa/pendaftaran-pilihan');
                        const data = await res.json();
                        if (data.success && data.data) {
                            this.dataPendaftaran = data.data;
                        } else {
                            this.dataPendaftaran = null;
                        }
                    } catch (e) {
                        console.error('Error fetching pendaftaran:', e);
                    }
                },

                async viewDetail(id) {
                    try {
                        const res = await fetch('/siswa/paket-menu-aktif/' + id);
                        const data = await res.json();
                        if (data.success) {
                            this.detailPaket = data.data;
                            this.showModal = true;
                        }
                    } catch (e) {
                        console.error('Error fetching detail:', e);
                    }
                },

                async submitPilihan() {
                    if (!this.pilihan1 || !this.pilihan2 || !this.pilihan3) {
                        this.formError = true;
                        this.formMessage = 'Silakan pilih ketiga paket menu prioritas Anda.';
                        return;
                    }

                    if (this.pilihan1 === this.pilihan2 || this.pilihan1 === this.pilihan3 || this.pilihan2 === this.pilihan3) {
                        this.formError = true;
                        this.formMessage = 'Pilihan paket menu tidak boleh ada yang sama (duplikat).';
                        return;
                    }

                    this.isSubmitting = true;
                    this.formMessage = '';
                    this.formError = false;

                    try {
                        const res = await fetch('/siswa/pendaftaran-pilihan', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({
                                pilihan: [this.pilihan1, this.pilihan2, this.pilihan3],
                            }),
                        });

                        const data = await res.json();

                        if (res.ok && data.success) {
                            this.formMessage = 'Pilihan paket prioritas Anda berhasil disimpan!';
                            this.formError = false;
                            await this.fetchPendaftaran();
                        } else {
                            this.formError = true;
                            this.formMessage = data.message || 'Terjadi kesalahan saat menyimpan pilihan.';
                        }
                    } catch (e) {
                        console.error('Error submitting pilihan:', e);
                        this.formError = true;
                        this.formMessage = 'Terjadi kesalahan jaringan saat menyimpan pilihan.';
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            }
        }
    </script>
</body>
</html>
