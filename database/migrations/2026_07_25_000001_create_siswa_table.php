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
        Schema::create('siswa', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('users_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nisn', 10)->unique();
            $table->string('nis', 10)->unique();
            $table->string('nama_lengkap', 150);
            $table->string('kelas_asal', 10);
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
