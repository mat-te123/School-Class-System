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
        Schema::create('detail_nilai_siswa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('nilai_leger_siswa_id')->constrained('nilai_leger_siswa')->onDelete('cascade');
            $table->foreignUuid('master_mata_pelajaran_id')->constrained('master_mata_pelajaran')->onDelete('cascade');
            $table->decimal('nilai_angka', 5, 2)->default(0.00);
            $table->string('predikat', 5)->nullable();

            $table->unique(['nilai_leger_siswa_id', 'master_mata_pelajaran_id'], 'unique_leger_mapel');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_nilai_siswa');
    }
};
