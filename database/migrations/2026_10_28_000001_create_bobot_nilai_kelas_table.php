<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bobot_nilai_kelas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('kelas_asal_id')->constrained('kelas_asal')->cascadeOnDelete();
            $table->foreignUuid('master_mata_pelajaran_id')->constrained('master_mata_pelajaran')->cascadeOnDelete();
            $table->decimal('bobot_persen', 5, 2)->default(0);
            $table->unique(['kelas_asal_id', 'master_mata_pelajaran_id'], 'unique_kelas_mapel_bobot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bobot_nilai_kelas');
    }
};
