<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proyeksi_universitas_id')
                  ->constrained('proyeksi_universitas')
                  ->onDelete('cascade');
            $table->string('nama_prodi', 200);
            $table->enum('jenjang', ['D3', 'D4', 'S1', 'S2', 'S3', 'Profesi'])->default('S1');
            $table->string('akreditasi_prodi', 20)->nullable();
            $table->integer('daya_tampung')->nullable();
            $table->integer('peminat_tahun_lalu')->nullable();
            $table->enum('kelompok_saintek_soshum', ['Saintek', 'Soshum', 'Campuran'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['proyeksi_universitas_id', 'kelompok_saintek_soshum']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
