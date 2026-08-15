<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporan_pesan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Pelapor (User Admin/Guru atau Siswa)
            $table->foreignUuid('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('siswa_id')->nullable()->constrained('siswa')->cascadeOnDelete();

            // Identitas Pelapor Guest (Siswa tanpa akun)
            $table->string('nisn', 20)->nullable();
            $table->string('nama', 100)->nullable();
            $table->string('kelas', 50)->nullable();

            // Detail Laporan
            $table->string('judul', 150);
            $table->string('kategori', 50)->default('umum'); // e.g. 'bug', 'saran', 'pengaduan', 'sistem'
            $table->text('pesan');
            $table->string('lampiran_path', 255)->nullable();

            // Status & Penanganan
            $table->enum('status', ['pending', 'diproses', 'selesai', 'ditolak'])->default('pending');
            $table->text('catatan_penanganan')->nullable();
            $table->foreignUuid('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexing
            $table->index('status');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_pesan');
    }
};
