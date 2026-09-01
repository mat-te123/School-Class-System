<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_pertukaran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->foreignUuid('periode_pendaftaran_id')->constrained('periode_pendaftaran')->onDelete('cascade');
            $table->foreignUuid('paket_asal_id')->constrained('paket_menu_pilihan')->onDelete('cascade');
            $table->foreignUuid('paket_tujuan_id')->constrained('paket_menu_pilihan')->onDelete('cascade');
            $table->text('alasan');
            $table->string('dokumen_persetujuan_path')->nullable();
            $table->enum('status', ['menunggu', 'disetujui', 'ditolak'])->default('menunggu');
            $table->text('catatan_admin')->nullable();
            $table->foreignUuid('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_tinjauan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_pertukaran');
    }
};
