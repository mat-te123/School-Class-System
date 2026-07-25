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
        Schema::create('nilai_leger_siswa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('siswa_id')->constrained('siswa')->onDelete('cascade');
            $table->string('tahun_ajaran', 10)->default('2024/2025');
            $table->string('semester', 10)->default('Genap');
            $table->decimal('rata_6_mapel', 5, 2)->default(0.00);
            $table->decimal('rata_keseluruhan', 5, 2)->default(0.00);
            $table->json('nilai_json')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['siswa_id', 'tahun_ajaran', 'semester'], 'unique_siswa_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nilai_leger_siswa');
    }
};
