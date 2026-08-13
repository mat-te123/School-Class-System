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
        Schema::table('detail_nilai_siswa', function (Blueprint $table) {
            // Index for AVG calculations and WHERE lookup by leger ID
            $table->index('nilai_leger_siswa_id', 'detail_nilai_siswa_leger_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_nilai_siswa', function (Blueprint $table) {
            $table->dropIndex('detail_nilai_siswa_leger_id_idx');
        });
    }
};
