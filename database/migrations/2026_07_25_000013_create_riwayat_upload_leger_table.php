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
        Schema::create('riwayat_upload_leger', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kelas_asal_id')->nullable()->constrained('kelas_asal')->nullOnDelete();
            $table->string('nama_kelas', 50);
            $table->string('angkatan', 20); // e.g. '2024/2025'
            $table->string('file_name', 255);
            $table->string('file_path', 255)->nullable();
            $table->integer('jumlah_siswa')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('completed');
            $table->text('error_message')->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Batasan Unik: 1 Kelas dan 1 Angkatan hanya boleh memiliki 1 file Excel Leger
            $table->unique(['nama_kelas', 'angkatan'], 'unique_kelas_angkatan_upload');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_upload_leger');
    }
};
